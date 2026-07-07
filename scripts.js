var dataSensor = [
    { id: "SNS-01", lokasi: "Saluran Induk Ngidul", debit: 12.4, tma: 42, suhu: 26.8, lembap: 68, status: "normal" },
    { id: "SNS-02", lokasi: "Percabangan Blok A",  debit: 8.7,  tma: 35, suhu: 27.1, lembap: 72, status: "normal" },
    { id: "SNS-03", lokasi: "Saluran Blok B",      debit: 3.2,  tma: 18, suhu: 28.3, lembap: 45, status: "rendah" },
    { id: "SNS-04", lokasi: "Bak Penampungan C1",  debit: 18.9, tma: 71, suhu: 26.2, lembap: 80, status: "tinggi" },
    { id: "SNS-05", lokasi: "Saluran Ngalor D",  debit: 6.5,  tma: 28, suhu: 27.8, lembap: 63, status: "normal" },
    { id: "SNS-06", lokasi: "Saluran Ngetan E",   debit: 1.1,  tma: 10, suhu: 29.0, lembap: 31, status: "kritis" },
    { id: "SNS-07", lokasi: "Saluran Petak 12",        debit: 9.3,  tma: 38, suhu: 26.5, lembap: 70, status: "normal" },
    { id: "SNS-08", lokasi: "Embung Ngulon",    debit: 7.8,  tma: 32, suhu: 27.4, lembap: 66, status: "normal" }
];


function renderTabel() {
    var tbody = document.getElementById("isi-tabel");
    var html = "";

    
    for (var i = 0; i < dataSensor.length; i++) {
        var s = dataSensor[i];

        
        var labelStatus = {
            "normal": "Normal",
            "tinggi": "Tinggi",
            "rendah": "Rendah",
            "kritis": "Kritis !"
        }[s.status] || s.status;

        var classStatus = "status-" + s.status;

        
        html += "<tr>";
        html += "<td>" + (i + 1) + "</td>";
        html += "<td>" + s.id + "</td>";
        html += "<td>" + s.lokasi + "</td>";
        html += "<td>" + s.debit.toFixed(1) + "</td>";
        html += "<td>" + s.tma + "</td>";
        html += "<td>" + s.suhu.toFixed(1) + "</td>";
        html += "<td>" + s.lembap + "</td>";
        html += "<td><span class='" + classStatus + "'>" + labelStatus + "</span></td>";
        html += "<td>" + waktuSekarang() + "</td>";
        html += "</tr>";
    }

    tbody.innerHTML = html;
    hitungRingkasan();
}


function hitungRingkasan() {
    var totalDebit   = 0;
    var totalTMA     = 0;
    var jumlahNormal = 0;

    for (var i = 0; i < dataSensor.length; i++) {
        totalDebit += dataSensor[i].debit;
        totalTMA   += dataSensor[i].tma;
        if (dataSensor[i].status === "normal") jumlahNormal++;
    }

    var n = dataSensor.length;

    document.getElementById("rata-debit").textContent  = (totalDebit / n).toFixed(1);
    document.getElementById("rata-tma").textContent    = Math.round(totalTMA / n);
    document.getElementById("sensor-aman").textContent = jumlahNormal + " dari " + n + " titik";
}



function perbaruiSensor() {
    for (var i = 0; i < dataSensor.length; i++) {
        var s = dataSensor[i];


        s.debit  = Math.max(0.5, s.debit + (Math.random() - 0.5));
        s.tma    = Math.max(5,   s.tma   + Math.round((Math.random() - 0.5) * 3));
        s.lembap = Math.min(100, Math.max(10, s.lembap + Math.round((Math.random() - 0.5) * 2)));

        
        if (s.tma < 15) s.status = "kritis";
        else if (s.tma < 25) s.status = "rendah";
        else if (s.tma > 65) s.status = "tinggi";
        else s.status = "normal";
    }

    renderTabel();
}



function waktuSekarang() {
    var now = new Date();
    var h = String(now.getHours()).padStart(2, "0");
    var m = String(now.getMinutes()).padStart(2, "0");
    var s = String(now.getSeconds()).padStart(2, "0");
    return h + ":" + m + ":" + s;
}


function kirimLaporan() {
    var nama    = document.getElementById("nama-pelapor").value.trim();
    var lokasi  = document.getElementById("lokasi-kendala").value.trim();
    var jenis   = document.getElementById("jenis-kendala").value;
    var pesanEl = document.getElementById("pesan-form");

    
    if (!nama || !lokasi || !jenis) {
        pesanEl.style.display    = "block";
        pesanEl.style.background = "#fdecea";
        pesanEl.style.border     = "1px solid #e74c3c";
        pesanEl.style.color      = "#c0392b";
        pesanEl.textContent      = "Mohon isi semua kolom sebelum mengirim laporan.";
        return;
    }

   
    pesanEl.style.display    = "block";
    pesanEl.style.background = "#e8f5e9";
    pesanEl.style.border     = "1px solid #2e7d32";
    pesanEl.style.color      = "#1b5e20";
    pesanEl.textContent      = "Laporan dari " + nama + " berhasil dikirim! Petugas akan segera menangani: \"" + jenis + "\" di " + lokasi + ".";

    
    setTimeout(function () {
        document.getElementById("nama-pelapor").value  = "";
        document.getElementById("lokasi-kendala").value = "";
        document.getElementById("jenis-kendala").value  = "";
    }, 500);
}

renderTabel();
setInterval(perbaruiSensor, 4000); 