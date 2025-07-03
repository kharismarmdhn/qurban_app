<?php
// qurban_app/admin/templates/scripts.php
// File ini akan di-include di bagian akhir body halaman untuk semua script JS

// Perhatikan: Script Flot Chart dan Jvectormap di sini adalah DEMO data dari INSPINIA.
// Anda perlu mengganti bagian data dengan query PHP untuk data dari database Anda.
?>
<script src="../assets/js/jquery-3.1.1.min.js"></script>
<script src="../assets/js/bootstrap.min.js"></script>
<script src="../assets/js/plugins/metisMenu/jquery.metisMenu.js"></script>
<script src="../assets/js/plugins/slimscroll/jquery.slimscroll.min.js"></script>

<script src="../assets/js/plugins/flot/jquery.flot.js"></script>
<script src="../assets/js/plugins/flot/jquery.flot.tooltip.min.js"></script>
<script src="../assets/js/plugins/flot/jquery.flot.spline.js"></script>
<script src="../assets/js/plugins/flot/jquery.flot.resize.js"></script>
<script src="../assets/js/plugins/flot/jquery.flot.pie.js"></script>
<script src="../assets/js/plugins/flot/jquery.flot.symbol.js"></script>
<script src="../assets/js/plugins/flot/jquery.flot.time.js"></script>

<script src="../assets/js/plugins/peity/jquery.peity.min.js"></script>
<script src="../assets/js/inspinia.js"></script>
<script src="../assets/js/plugins/pace/pace.min.js"></script>

<script src="../assets/js/plugins/jquery-ui/jquery-ui.min.js"></script>

<script src="../assets/js/plugins/jvectormap/jquery-jvectormap-2.0.2.min.js"></script>
<script src="../assets/js/plugins/jvectormap/jquery-jvectormap-world-mill-en.js"></script>

<script src="../assets/js/plugins/easypiechart/jquery.easypiechart.js"></script>

<script src="../assets/js/plugins/sparkline/jquery.sparkline.min.js"></script>
<script>
    // Fungsi gd() untuk Flot Chart
    function gd(year, month, day) {
        return new Date(year, month - 1, day).getTime();
    }

    $(document).ready(function() {
        // Inisialisasi EasyPieChart (jika ada widget yang menggunakan)
        $('.chart').easyPieChart({
            barColor: '#f8ac59',
            scaleLength: 5,
            lineWidth: 4,
            size: 80
        });

        $('.chart2').easyPieChart({
            barColor: '#1c84c6',
            scaleLength: 5,
            lineWidth: 4,
            size: 80
        });

        // =========================================================================
        // PENTING: Ganti DATA DUMMY ini dengan data dari database Anda!
        // Contoh untuk Flot Chart:
        // Untuk data keuangan, Anda bisa mengambil total pemasukan/pengeluaran per bulan/tahun dari transaksi_keuangan
        // dan memasukkannya ke variabel JavaScript ini melalui PHP echo.
        // =========================================================================

        // =========================================================================
        // Data untuk Flot Chart diambil dari PHP di halaman utama (index.php)
        // Pastikan variabel 'js_data_pemasukan_chart' dan 'js_data_pengeluaran_chart'
        // di-echo dari PHP sebelum script ini di-load.
        // =========================================================================

        // Mendapatkan data dari variabel JavaScript yang di-echo dari PHP
        // Pastikan variabel ini didefinisikan di halaman utama sebelum memanggil scripts.php
        var dataPemasukanChart = (typeof js_data_pemasukan_chart !== 'undefined') ? JSON.parse('[' + js_data_pemasukan_chart + ']') : [];
        var dataPengeluaranChart = (typeof js_data_pengeluaran_chart !== 'undefined') ? JSON.parse('[' + js_data_pengeluaran_chart + ']') : [];

        var dataset = [
            {
                label: "Pemasukan",
                data: dataPemasukanChart, // Menggunakan data dari PHP
                color: "#1ab394",
                bars: {
                    show: true,
                    align: "center",
                    barWidth: 24 * 60 * 60 * 600,
                    lineWidth:0
                }
            }, {
                label: "Pengeluaran",
                data: dataPengeluaranChart, // Menggunakan data dari PHP
                yaxis: 2,
                color: "#1C84C6",
                lines: {
                    lineWidth:1,
                    show: true,
                    fill: true,
                    fillColor: {
                        colors: [{
                            opacity: 0.2
                        }, {
                            opacity: 0.4
                        }]
                    }
                },
                splines: {
                    show: false,
                    tension: 0.6,
                    lineWidth: 1,
                    fill: 0.1
                },
            }
        ];
        
        var options = {
            xaxis: {
                mode: "time",
                tickSize: [3, "day"],
                tickLength: 0,
                axisLabel: "Tanggal", // Ubah label
                axisLabelUseCanvas: true,
                axisLabelFontSizePixels: 12,
                axisLabelFontFamily: 'Arial',
                axisLabelPadding: 10,
                color: "#d5d5d5"
            },
            yaxes: [{
                position: "left",
                max: 1070, // Sesuaikan max Y-axis
                color: "#d5d5d5",
                axisLabelUseCanvas: true,
                axisLabelFontSizePixels: 12,
                axisLabelFontFamily: 'Arial',
                axisLabelPadding: 3
            }, {
                position: "right",
                clolor: "#d5d5d5",
                axisLabelUseCanvas: true,
                axisLabelFontSizePixels: 12,
                axisLabelFontFamily: ' Arial',
                axisLabelPadding: 67
            }
            ],
            legend: {
                noColumns: 1,
                labelBoxBorderColor: "#000000",
                position: "nw"
            },
            grid: {
                hoverable: false,
                borderWidth: 0
            }
        };

        $.plot($("#flot-dashboard-chart"), dataset, options);

        // Contoh untuk Jvectormap:
        // Jika tidak digunakan, Anda bisa menghapus bagian ini atau widget map dari HTML dashboard.
        var mapData = {
            "ID": 500, // Contoh data untuk Indonesia
            "MY": 250, // Contoh data untuk Malaysia
            "SG": 100, // Contoh data untuk Singapore
            // ... Tambahkan data relevan jika Anda ingin menampilkan statistik per negara/wilayah
        };

        $('#world-map').vectorMap({
            map: 'world_mill_en',
            backgroundColor: "transparent",
            regionStyle: {
                initial: {
                    fill: '#e4e4e4',
                    "fill-opacity": 0.9,
                    stroke: 'none',
                    "stroke-width": 0,
                    "stroke-opacity": 0
                }
            },
            series: {
                regions: [{
                    values: mapData,
                    scale: ["#1ab394", "#22d6b1"],
                    normalizeFunction: 'polynomial'
                }]
            },
        });
    });
</script>