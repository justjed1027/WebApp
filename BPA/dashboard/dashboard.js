// Chart.js Progress Example
const ctx = document.getElementById('progressChart').getContext('2d');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: ['Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
    datasets: [
      {
        label: 'Study Hours',
        data: [20, 25, 30, 28, 22, 18, 25],
        backgroundColor: '#3a57e8'
      },
      {
        label: 'Assignments',
        data: [10, 12, 14, 13, 9, 8, 12],
        backgroundColor: '#f97316'
      }
    ]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true } }
  }
});
