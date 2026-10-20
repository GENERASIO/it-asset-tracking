// ITAssetLocation - helper kecil untuk IT Asset Agent macOS.
//
// macOS hanya mengizinkan lokasi diambil oleh aplikasi (bundle .app dengan
// NSLocationUsageDescription) yang disetujui user, bukan oleh script bash. Jadi checkin.sh
// menjalankan app ini lewat `open -W -n -g -a ITAssetLocation.app --args <file-output>`,
// dan app ini menulis satu baris ke <file-output>:
//
//   OK <latitude> <longitude> <akurasi_meter>
//   ERROR <alasan>
//
// lalu keluar. Pertama kali dijalankan, macOS menampilkan pop-up izin lokasi.

import AppKit
import CoreLocation

final class Locator: NSObject, CLLocationManagerDelegate {
    private let manager = CLLocationManager()
    private let outputPath: String
    private var finished = false

    init(outputPath: String) {
        self.outputPath = outputPath
        super.init()
        manager.delegate = self
        manager.desiredAccuracy = kCLLocationAccuracyBest
    }

    func start() {
        guard CLLocationManager.locationServicesEnabled() else {
            finish("ERROR location_services_off")
            return
        }

        // Kalau izin belum pernah ditanyakan, beri waktu lebih lama supaya user sempat
        // membaca pop-up dan klik Allow.
        let timeout: TimeInterval = manager.authorizationStatus == .notDetermined ? 90 : 30
        DispatchQueue.main.asyncAfter(deadline: .now() + timeout) { [weak self] in
            self?.finish("ERROR timeout")
        }

        handle(manager.authorizationStatus)
    }

    private func handle(_ status: CLAuthorizationStatus) {
        switch status {
        case .notDetermined:
            manager.requestWhenInUseAuthorization()
        case .denied:
            finish("ERROR denied")
        case .restricted:
            finish("ERROR restricted")
        default:
            manager.startUpdatingLocation()
        }
    }

    func locationManagerDidChangeAuthorization(_ manager: CLLocationManager) {
        handle(manager.authorizationStatus)
    }

    func locationManager(_ manager: CLLocationManager, didUpdateLocations locations: [CLLocation]) {
        guard let location = locations.last, location.horizontalAccuracy >= 0 else { return }

        finish(String(
            format: "OK %.7f %.7f %d",
            location.coordinate.latitude,
            location.coordinate.longitude,
            Int(location.horizontalAccuracy.rounded())
        ))
    }

    func locationManager(_ manager: CLLocationManager, didFailWithError error: Error) {
        // locationUnknown hanya berarti "belum dapat posisi" - CoreLocation akan terus mencoba.
        if (error as? CLError)?.code == .locationUnknown { return }
        finish("ERROR \(error.localizedDescription)")
    }

    private func finish(_ line: String) {
        guard !finished else { return }
        finished = true

        manager.stopUpdatingLocation()
        try? (line + "\n").write(toFile: outputPath, atomically: true, encoding: .utf8)
        NSApp.terminate(nil)
    }
}

// Versi macOS lama kadang menyisipkan argumen -psn_... saat app dibuka lewat `open`.
guard let outputPath = CommandLine.arguments.dropFirst().first(where: { !$0.hasPrefix("-psn") }) else {
    FileHandle.standardError.write("Pemakaian: ITAssetLocation <file-output>\n".data(using: .utf8)!)
    exit(2)
}

let app = NSApplication.shared
app.setActivationPolicy(.accessory)

let locator = Locator(outputPath: outputPath)
DispatchQueue.main.async { locator.start() }

app.run()
