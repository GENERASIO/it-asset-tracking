import { Html5Qrcode } from "html5-qrcode";

window.addEventListener('DOMContentLoaded', () => {
    const readerEl = document.getElementById("reader");
    if (!readerEl) return;

    const scanner = new Html5Qrcode("reader");
    const resultBox = document.getElementById("result");
    const notFoundBox = document.getElementById("not-found");
    const btnScanAgain = document.getElementById("btn-scan-again");

    scanner.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: 220 },
        (decodedText) => onScanSuccess(decodedText),
        () => {}
    ).catch((err) => {
        document.getElementById("camera-error").classList.remove("hidden");
        document.getElementById("camera-error").innerText =
            "Gagal mengakses kamera: " + err;
    });

    function onScanSuccess(assetCode) {
        scanner.pause(true);

        fetch("/scan/lookup", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ asset_code: assetCode }),
        })
        .then((res) => res.json())
        .then((data) => {
            if (data.found) {
                resultBox.classList.remove("hidden");
                notFoundBox.classList.add("hidden");
                document.getElementById("r-code").innerText = data.asset.asset_code;
                document.getElementById("r-name").innerText = data.asset.name;
                document.getElementById("r-status").innerText = "Status: " + data.asset.status;
                document.getElementById("r-location").innerText = "Lokasi: " + data.asset.location.name;
                document.getElementById("btn-detail").href = "/assets/" + data.asset.id;
            } else {
                notFoundBox.innerText = data.message;
                notFoundBox.classList.remove("hidden");
                resultBox.classList.add("hidden");
            }
        })
        .catch(() => {
            notFoundBox.innerText = "Gagal menghubungi server.";
            notFoundBox.classList.remove("hidden");
        });
    }

    btnScanAgain?.addEventListener("click", () => {
        resultBox.classList.add("hidden");
        notFoundBox.classList.add("hidden");
        scanner.resume();
    });
});