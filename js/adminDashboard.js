document.addEventListener('DOMContentLoaded', function () {
    const labels = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli'];
    
    const data = {
        labels: labels,
        datasets: [
            {
                label: 'Pendapatan',
                data: [15, 25, 10, 18, 12, 28, 30],
                fill: false,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.5)',
                tension: 0.3
            },
            {
                label: 'Pengeluaran',
                data: [10, 20, 15, 12, 10, 20, 25],
                fill: false,
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.5)',
                tension: 0.3
            }
        ]
    };

    const config = {
        type: 'line',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true, 
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value + 'jt';
                        }
                    }
                }
            }
        }
    };

    const canvasElement = document.getElementById('pendapatanChart');
    if (canvasElement) {
        new Chart(canvasElement.getContext('2d'), config);
    }
});