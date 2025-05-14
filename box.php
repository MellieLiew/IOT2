<?php
$boxId = "5f2b56f4263635001c1dd1fd";
?>

<!-- Countdown -->
<div style="text-align:center; font-family:Arial, sans-serif; margin: 10px;">
  <span style="font-size: 16px;">🔄 Auto-refreshing in <strong id="countdown">15</strong> seconds...</span>
</div>

<!-- Map -->
<div id="map" style="height: 400px; border-radius: 12px; margin: 20px;"></div>

<!-- Cards -->
<div class="card-container" style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; padding: 20px; font-family:Arial, sans-serif;"></div>

<!-- Chart -->
<div id="chart-container" style="width: 90%; max-width: 900px; margin: 30px auto;">
  <canvas id="sensorChart" style="background-color: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);"></canvas>
</div>

<!-- External Scripts -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const boxId = "<?php echo $boxId; ?>";
let map, marker, chart, countdown = 15;

function fetchDataAndRender() {
    fetch(`https://api.opensensemap.org/boxes/${boxId}`)
        .then(res => res.json())
        .then(data => {
            const { loc, name, sensors } = data;
            const lat = loc[0].geometry.coordinates[1];
            const lng = loc[0].geometry.coordinates[0];

            // Map Initialization
            if (!map) {
                map = L.map('map').setView([lat, lng], 16);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap'
                }).addTo(map);
                marker = L.marker([lat, lng]).addTo(map).bindPopup(`<b>${name}</b>`).openPopup();
            } else {
                marker.setLatLng([lat, lng]);
            }

            // Cards
            const container = document.querySelector(".card-container");
            container.innerHTML = "";
            let labels = [], values = [];

            sensors.forEach(sensor => {
                const title = sensor.title;
                const unit = sensor.unit;
                const m = sensor.lastMeasurement;
                const value = m ? m.value : "N/A";
                const time = m ? m.createdAt : "N/A";

                if (value !== "N/A") {
                    labels.push(title);
                    values.push(Number(value));
                }

                const card = document.createElement("div");
                card.style.cssText = `
                    width: 240px;
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 8px 16px rgba(0,0,0,0.08);
                    padding: 20px;
                    text-align: left;
                    transition: 0.3s ease;
                `;
                card.innerHTML = `
                    <h3 style="margin: 0 0 10px; font-size: 18px;">${title}</h3>
                    <p style="margin: 4px 0;"><strong>Value:</strong> ${value} ${unit}</p>
                    <p style="margin: 4px 0; font-size: 12px; color: gray;"><strong>Time:</strong> ${time}</p>
                `;
                container.appendChild(card);
            });

            // Chart.js
            const ctx = document.getElementById("sensorChart").getContext("2d");
            const chartData = {
                labels: labels,
                datasets: [{
                    label: "Sensor Values",
                    data: values,
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            };

            if (!chart) {
                chart = new Chart(ctx, {
                    type: "bar",
                    data: chartData,
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            } else {
                chart.data = chartData;
                chart.update();
            }
        });
}

// Countdown Timer + Refresh
function startCountdown() {
    const counter = document.getElementById("countdown");
    const timer = setInterval(() => {
        countdown--;
        counter.textContent = countdown;
        if (countdown <= 0) {
            clearInterval(timer);
            countdown = 15;
            fetchDataAndRender();
            startCountdown();
        }
    }, 1000);
}

// Initial load
document.addEventListener("DOMContentLoaded", () => {
    fetchDataAndRender();
    startCountdown();
});
</script>
