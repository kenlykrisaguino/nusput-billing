<?php

$dashboard = $app->DashboardBE()->index();

$byJenjangData = $dashboard['by_jenjang'];
$jenjangLabels = [];
$lunasData = [];
$belumLunasData = [];

foreach ($byJenjangData as $item) {
    $jenjangLabels[] = $item['jenjang'];
    $lunasData[] = $item['total'] - $item['belum_lunas']; 
    $belumLunasData[] = (int)$item['belum_lunas'];
}

$js_jenjangLabels = json_encode($jenjangLabels);
$js_lunasData = json_encode($lunasData);
$js_belumLunasData = json_encode($belumLunasData);


$percentageData = $dashboard['percentage'];
$totalLunas = ($percentageData['total'] ?? 0) - ($percentageData['belum_lunas'] ?? 0);
$totalBelumLunas = $percentageData['belum_lunas'] ?? 0;

$js_percentageSeries = json_encode([$totalLunas, (int)$totalBelumLunas]);

$trendData = $dashboard['payment_trend'] ?? ['labels' => [], 'data' => []];
$js_trendData = json_encode($trendData);
?>

<div class="p-4 sm:p-6 lg:p-8">

    <h3 class="font-semibold text-xl text-slate-700">Dashboard</h3>

    <section id="dashboard" class="mt-4">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="col-span-2 row-span-2 bg-white p-4 rounded-md">
                <p class="text-lg font-semibold mb-1">Status Pembayaran per Jenjang</p>
                <section id="status-per-jenjang"></section>
            </div>
            <div class="bg-sky-200 p-4 rounded-md">
                <p class="text-lg font-semibold mb-1">Jumlah Siswa Aktif</p>
                <p><span class="font-bold text-3xl"><?= $dashboard['count_siswa']['active'] ?></span> Siswa aktif</p>
            </div>
            <div class="bg-sky-200 p-4 rounded-md">
                <p class="text-lg font-semibold mb-1">Jumlah Alumni Terdaftar</p>
                <p><span class="font-bold text-3xl"><?= $dashboard['count_siswa']['inactive'] ?></span> Alumni</p>
            </div>
            <div class="bg-white p-4 rounded-md">
                <p class="text-lg font-semibold mb-1">Persentase kepatuhan</p>
                <section id="percentage-donut-chart"></section>
            </div>
            <div class="col-span-2 bg-white p-4 rounded-md">
                <p class="text-lg font-semibold mb-1">Trend Pemasukan 12 Bulan Terakhir</p>
                <div id="payment-trend-chart"></div>
            </div>

        </div>
    </section>

</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
    var statusJenjangOptions = {
        series: [{
            name: 'Lunas',
            data: <?= $js_lunasData ?> 
        }, {
            name: 'Belum Lunas',
            data: <?= $js_belumLunasData ?>
        }],

        chart: {
            type: 'bar',
            height: 350,
            stacked: true,
            stackType: '100%',
            toolbar: {
                show: false
            }
        },

        plotOptions: {
            bar: {
                horizontal: false,
            },
        },
        
        stroke: {
            width: 1,
            colors: ['#fff']
        },

        xaxis: {
            categories: <?= $js_jenjangLabels ?>,
        },

        tooltip: {
            y: {
                formatter: function (val) {
                    return val + " tagihan"
                }
            }
        },

        fill: {
            opacity: 1
        },

        colors: ['#16A34A', '#DC2626'], 

        legend: {
            position: 'top',
            horizontalAlign: 'left',
            offsetX: 40
        }
    };

    var statusJenjang = new ApexCharts(document.querySelector("#status-per-jenjang"), statusJenjangOptions);
    statusJenjang.render();

    var percentageOptions = {
        series: <?= $js_percentageSeries ?>,

        chart: {
            type: 'donut',
            height: 350
        },

        labels: ['Lunas', 'Belum Lunas'],

        colors: ['#16A34A', '#DC2626'], 

        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    width: 200
                },
                legend: {
                    position: 'bottom'
                }
            }
        }],

        dataLabels: {
            enabled: true,
            formatter: function (val, opts) {
                return parseFloat(val).toFixed(1) + '%'
            }
        },
        
        legend: {
            position: 'bottom',
            formatter: function(seriesName, opts) {
                return seriesName + ": " + opts.w.globals.series[opts.seriesIndex]
            }
        },

        tooltip: {
            y: {
                formatter: function(val) {
                    return val + " tagihan"
                }
            }
        }
    };

    var percentageChart = new ApexCharts(document.querySelector("#percentage-donut-chart"), percentageOptions);
    percentageChart.render();

    const trendData = <?= $js_trendData ?>;
    var paymentTrendOptions = {
        series: [{
            name: 'Total Pemasukan',
            data: trendData.data 
        }],

        chart: {
            type: 'area', 
            height: 350,
            zoom: {
                enabled: false 
            },
            toolbar: {
                show: true,
                tools: {
                    download: true,
                    selection: false,
                    zoom: false,
                    zoomin: false,
                    zoomout: false,
                    pan: false,
                    reset: false
                }
            }
        },
        
        dataLabels: {
            enabled: false
        },

        stroke: {
            curve: 'smooth'
        },
        
        xaxis: {
            categories: trendData.labels
        },
        
        tooltip: {
            x: {
                format: 'MMMM yyyy'
            },
            y: {
                formatter: function (val) {
                    return "Rp " + val.toLocaleString('id-ID');
                }
            },
        },
        
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.7,
                opacityTo: 0.2,
                stops: [0, 90, 100]
            }
        },
    };

    var paymentTrend = new ApexCharts(document.querySelector("#payment-trend-chart"), paymentTrendOptions);
    paymentTrend.render();

</script>