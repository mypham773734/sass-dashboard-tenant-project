// Chart initialization
const ctx = document.getElementById('progressChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Tuần 1', 'Tuần 2', 'Tuần 3', 'Tuần 4', 'Tuần 5', 'Tuần 6'],
        datasets: [{
            label: 'Tiến độ %',
            data: [30, 45, 55, 70, 82, 88],
            borderColor: '#4f46e5',
            backgroundColor: 'rgba(79, 70, 229, 0.05)',
            borderWidth: 2.5,
            pointBackgroundColor: '#4f46e5',
            pointBorderColor: '#fff',
            pointRadius: 4,
            pointHoverRadius: 6,
            tension: 0.3,
            fill: true,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    boxWidth: 12,
                    font: {
                        size: 11
                    }
                }
            },
            tooltip: {
                backgroundColor: '#0f172a',
                titleColor: '#f1f5f9'
            }
        },
        scales: {
            y: {
                grid: {
                    color: '#e2e8f0'
                },
                beginAtZero: true,
                max: 100,
                title: {
                    display: true,
                    text: 'Hoàn thành (%)',
                    font: {
                        size: 10
                    }
                }
            },
            x: {
                grid: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Tuần',
                    font: {
                        size: 10
                    }
                }
            }
        }
    }
});