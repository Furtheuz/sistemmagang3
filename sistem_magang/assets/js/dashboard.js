// Toggle sidebar
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('visible');
    // Update main content margin
    const mainContent = document.querySelector('.main-content');
    if (sidebar.classList.contains('visible')) {
        mainContent.style.marginLeft = '280px';
    } else {
        mainContent.style.marginLeft = '0';
    }
}

// Initialize Swiper slideshow
const swiper = new Swiper('.swiper', {
    loop: true,
    effect: 'fade',
    pagination: {
        el: '.swiper-pagination',
        clickable: true,
        dynamicBullets: true,
    },
    navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
    },
    autoplay: {
        delay: 4000,
        disableOnInteraction: false,
        pauseOnMouseEnter: false,
    },
});

// Initialize charts for admin or pembimbing roles
if (userRole === 'admin' || userRole === 'pembimbing') {
    // Create gradient for charts
    function createGradient(ctx, color1, color2) {
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, color1);
        gradient.addColorStop(1, color2);
        return gradient;
    }

    // Ensure statsData exists
    if (typeof statsData === 'undefined') {
        console.error('statsData is not defined');
        var statsData = {
            total_peserta: 0,
            peserta_verified: 0,
            peserta_pending: 0,
            peserta_rejected: 0,
            total_institusi: 0,
            total_arsip: 0,
            arsip_bulan_ini: 0,
            peserta_aktif: 0,
            peserta_alumni: 0
        };
    }

    // Chart configuration for Total Peserta
    const pesertaCanvas = document.getElementById('chart-total-peserta');
    if (pesertaCanvas) {
        new Chart(pesertaCanvas, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr'],
                datasets: [{
                    label: 'Total Peserta',
                    data: [
                        statsData.total_peserta * 0.9,
                        statsData.total_peserta * 0.95,
                        statsData.total_peserta,
                        statsData.total_peserta * 1.05
                    ],
                    borderColor: createGradient(pesertaCanvas.getContext('2d'), '#1B5E20', '#FDD835'),
                    borderWidth: 2,
                    pointRadius: 4,
                    tension: 0.4,
                    fill: true,
                    backgroundColor: 'rgba(27, 94, 32, 0.1)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleFont: { size: 12, family: 'Inter' },
                        bodyFont: { size: 10, family: 'Inter' }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        title: { display: true, text: 'Bulan' }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#e5e7eb' },
                        title: { display: true, text: 'Jumlah' }
                    }
                }
            }
        });
    } else {
        console.error('Canvas #chart-total-peserta not found');
    }

    // Chart configuration for Total Arsip
    const arsipCanvas = document.getElementById('chart-total-arsip');
    if (arsipCanvas) {
        new Chart(arsipCanvas, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr'],
                datasets: [{
                    label: 'Total Arsip',
                    data: [
                        statsData.total_arsip * 0.9,
                        statsData.total_arsip * 0.95,
                        statsData.total_arsip,
                        statsData.total_arsip * 1.05
                    ],
                    borderColor: createGradient(arsipCanvas.getContext('2d'), '#1B5E20', '#FDD835'),
                    borderWidth: 2,
                    pointRadius: 4,
                    tension: 0.4,
                    fill: true,
                    backgroundColor: 'rgba(27, 94, 32, 0.1)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleFont: { size: 12, family: 'Inter' },
                        bodyFont: { size: 10, family: 'Inter' }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        title: { display: true, text: 'Bulan' }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#e5e7eb' },
                        title: { display: true, text: 'Jumlah' }
                    }
                }
            }
        });
    } else {
        console.error('Canvas #chart-total-arsip not found');
    }

    // Chart configuration for Total Institusi
    const institusiCanvas = document.getElementById('chart-total-institusi');
    if (institusiCanvas) {
        new Chart(institusiCanvas, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr'],
                datasets: [{
                    label: 'Total Institusi',
                    data: [
                        statsData.total_institusi * 0.9,
                        statsData.total_institusi * 0.95,
                        statsData.total_institusi,
                        statsData.total_institusi * 1.05
                    ],
                    borderColor: createGradient(institusiCanvas.getContext('2d'), '#1B5E20', '#FDD835'),
                    borderWidth: 2,
                    pointRadius: 4,
                    tension: 0.4,
                    fill: true,
                    backgroundColor: 'rgba(27, 94, 32, 0.1)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: true,
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleFont: { size: 12, family: 'Inter' },
                        bodyFont: { size: 10, family: 'Inter' }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        title: { display: true, text: 'Bulan' }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#e5e7eb' },
                        title: { display: true, text: 'Jumlah' }
                    }
                }
            }
        });
    } else {
        console.error('Canvas #chart-total-institusi not found');
    }

    // Candlestick chart for all stats
    const statsCanvas = document.getElementById('statsChart');
    if (statsCanvas) {
        const candlestickData = [
            { x: 'Total Peserta', o: statsData.total_peserta * 0.9, h: statsData.total_peserta * 1.1, l: statsData.total_peserta * 0.8, c: statsData.total_peserta },
            { x: 'Terverifikasi', o: statsData.peserta_verified * 0.9, h: statsData.peserta_verified * 1.1, l: statsData.peserta_verified * 0.8, c: statsData.peserta_verified },
            { x: 'Pending', o: statsData.peserta_pending * 0.9, h: statsData.peserta_pending * 1.1, l: statsData.peserta_pending * 0.8, c: statsData.peserta_pending },
            { x: 'Ditolak', o: statsData.peserta_rejected * 0.9, h: statsData.peserta_rejected * 1.1, l: statsData.peserta_rejected * 0.8, c: statsData.peserta_rejected },
            { x: 'Total Institusi', o: statsData.total_institusi * 0.9, h: statsData.total_institusi * 1.1, l: statsData.total_institusi * 0.8, c: statsData.total_institusi },
            { x: 'Total Arsip', o: statsData.total_arsip * 0.9, h: statsData.total_arsip * 1.1, l: statsData.total_arsip * 0.8, c: statsData.total_arsip },
            { x: 'Arsip Bulan Ini', o: statsData.arsip_bulan_ini * 0.9, h: statsData.arsip_bulan_ini * 1.1, l: statsData.arsip_bulan_ini * 0.8, c: statsData.arsip_bulan_ini },
            { x: 'Peserta Aktif', o: statsData.peserta_aktif * 0.9, h: statsData.peserta_aktif * 1.1, l: statsData.peserta_aktif * 0.8, c: statsData.peserta_aktif },
            { x: 'Peserta Alumni', o: statsData.peserta_alumni * 0.9, h: statsData.peserta_alumni * 1.1, l: statsData.peserta_alumni * 0.8, c: statsData.peserta_alumni }
        ];

        new Chart(statsCanvas, {
            type: 'line',
            data: {
                datasets: [{
                    label: 'Statistik Magang',
                    data: candlestickData,
                    borderColor: createGradient(statsCanvas.getContext('2d'), '#1B5E20', '#FDD835'),
                    borderWidth: 2,
                    pointRadius: 0,
                    fill: false,
                    parsing: { yAxisKey: 'c' }
                }, {
                    label: 'High-Low Range',
                    data: candlestickData,
                    type: 'line',
                    borderColor: 'rgba(0,0,0,0.2)',
                    borderWidth: 1,
                    pointRadius: 0,
                    segment: {
                        borderColor: ctx => ctx.p0.parsed.y < ctx.p1.parsed.y ? '#ef4444' : '#10b981',
                    },
                    parsing: { yAxisKey: 'h' }
                }, {
                    label: 'Low',
                    data: candlestickData,
                    type: 'line',
                    borderColor: 'rgba(0,0,0,0.2)',
                    borderWidth: 1,
                    pointRadius: 0,
                    parsing: { yAxisKey: 'l' }
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { size: 12, family: 'Inter' },
                            padding: 15
                        }
                    },
                    tooltip: {
                        enabled: true,
                        mode: 'nearest',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                const data = context.raw;
                                return [
                                    `Metric: ${context.label}`,
                                    `Open: ${data.o}`,
                                    `High: ${data.h}`,
                                    `Low: ${data.l}`,
                                    `Close: ${data.c}`
                                ];
                            }
                        },
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleFont: { size: 12, family: 'Inter' },
                        bodyFont: { size: 10, family: 'Inter' },
                        padding: 8
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Metrics',
                            font: { size: 12, family: 'Inter' }
                        },
                        grid: { display: false }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Jumlah',
                            font: { size: 12, family: 'Inter' }
                        },
                        beginAtZero: true,
                        grid: { color: '#e5e7eb' }
                    }
                },
                animation: {
                    duration: 800,
                    easing: 'easeOutCubic'
                }
            }
        });
    } else {
        console.error('Canvas #statsChart not found');
    }

    // Animate stats cards and circular progress
    document.addEventListener('DOMContentLoaded', function() {
        // Stats cards animation
        const cards = document.querySelectorAll('.stats-card');
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.5s ease';
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 50);
            }, index * 100);
        });

        // Circular progress animation with dynamic color
        const circles = document.querySelectorAll('.circle-progress');
        circles.forEach(circle => {
            const value = parseFloat(circle.dataset.value);
            const fgCircle = circle.querySelector('.circle-fg');
            const offset = 283 - (283 * value) / 100;
            fgCircle.style.strokeDashoffset = offset;
            // Dynamic color based on value
            const stop1 = fgCircle.closest('svg').querySelector('stop[offset="0%"]');
            const stop2 = fgCircle.closest('svg').querySelector('stop[offset="100%"]');
            if (value > 75) {
                stop1.setAttribute('stop-color', '#1B5E20');
                stop2.setAttribute('stop-color', '#FDD835');
            } else if (value > 50) {
                stop1.setAttribute('stop-color', '#388E3C');
                stop2.setAttribute('stop-color', '#FFCA28');
            } else {
                stop1.setAttribute('stop-color', '#4CAF50');
                stop2.setAttribute('stop-color', '#FFB300');
            }
        });
    });
}