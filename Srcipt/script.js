  document.getElementById("settingsBtn").onclick = function() {
    document.getElementById("mySidebar").style.width = "250px";
    };

  function closeSidebar() {
    document.getElementById("mySidebar").style.width = "0";
    }

  document.addEventListener("contextmenu", e => e.preventDefault());

  const links = document.querySelectorAll('.nav-links a');

  const activePage = localStorage.getItem('activePage');
  if (activePage) {
    links.forEach(link => {
      if (link.getAttribute('href') === activePage) {
        link.classList.add('active');
      }
    });
  }

  links.forEach(link => {
    link.addEventListener('click', function() {
  
      links.forEach(l => l.classList.remove('active'));

      this.classList.add('active');

      localStorage.setItem('activePage', this.getAttribute('href'));
    });
  });

  const tabButtons = document.querySelectorAll(".tab-button");
  const contentContainers = document.querySelectorAll(".content-container");

  tabButtons.forEach((button) => {
    button.addEventListener("click", () => {
      
      tabButtons.forEach((btn) => btn.classList.remove("active"));
      contentContainers.forEach((content) => content.classList.remove("active"));

      button.classList.add("active");
      document.getElementById(button.dataset.target).classList.add("active");
    });
  });

  document.addEventListener("keydown", e => {
    if (e.ctrlKey && ["u", "U", "s", "S", "i", "I", "j", "J","C","c"].includes(e.key)) {
      e.preventDefault();
    }
    if (e.key === "F12") {
      e.preventDefault();
    }
  });

  const ctx = document.getElementById('rfidChart').getContext('2d');
let chart;

function loadRFIDData() {
  fetch('../FetchData/fetch_data.php')
    .then(res => res.json())
    .then(data => {
      // Update counters
      document.getElementById('studentCount').textContent = data.counts['Student'] || 0;
      document.getElementById('facultyCount').textContent = data.counts['Faculty/Staff'] || 0;
      document.getElementById('guestCount').textContent = data.counts['Guest'] || 0;
      document.getElementById('accessdenied').textContent = data.counts['Access Denied'] || 0;

      // Prepare chart data
      const hours = [...new Set(data.graph.map(item => item.hour))];
      const studentData = hours.map(h => {
        const f = data.graph.find(g => g.hour == h && g.role == 'Student');
        return f ? f.count : 0;
      });
      const facultyData = hours.map(h => {
        const f = data.graph.find(g => g.hour == h && g.role == 'Faculty/Staff');
        return f ? f.count : 0;
      });
      const guestData = hours.map(h => {
        const f = data.graph.find(g => g.hour == h && g.role == 'Guest');
        return f ? f.count : 0;
      });
      const accessDeniedData = hours.map(h => {
        const f = data.graph.find(g => g.hour == h && g.role == 'Access Denied');
        return f ? f.count : 0;
      });

      if (chart) chart.destroy();

      chart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: hours.map(h => `${h}:00`),
          datasets: [
            {
              label: 'Students',
              data: studentData,
              borderColor: '#2563eb',
              backgroundColor: 'rgba(37, 99, 235, 0.2)',
              fill: true,
              tension: 0.3
            },
            {
              label: 'Faculty/Staff',
              data: facultyData,
              borderColor: '#16a34a',
              backgroundColor: 'rgba(22, 163, 74, 0.2)',
              fill: true,
              tension: 0.3
            },
            {
              label: 'Guests',
              data: guestData,
              borderColor: '#dc2626',
              backgroundColor: 'rgba(220, 38, 38, 0.2)',
              fill: true,
              tension: 0.3
            },
            {
              label: 'Access Denied',
              data: accessDeniedData,
              borderColor: '#f59e0b',
              backgroundColor: 'rgba(245, 158, 11, 0.2)',
              fill: true,
              tension: 0.3
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false, // makes it fill container height
          plugins: {
            legend: { position: 'bottom' },
            title: {
              display: true,
              text: 'Hourly RFID Tap Activity',
              font: { size: 18 }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: { stepSize: 1 }
            }
          }
        }
      });
    });
}

// Auto-refresh every 5 seconds
setInterval(loadRFIDData, 5000);
loadRFIDData();