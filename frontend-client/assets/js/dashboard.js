function protectPage() {
    const user = localStorage.getItem("loggedInUser");

    if (!user) {
        window.location.href = "login.html";
        return;
    }

    const parsedUser = JSON.parse(user);
    const welcomeText = document.getElementById("welcomeText");

    if (welcomeText) {
        welcomeText.textContent = "Welcome back, " + 
            (parsedUser.full_name || parsedUser.name || parsedUser.email || "User") + ".";
    }
}

async function loadDashboardSummary() {
    try {
        const response = await fetch(API_BASE_URL + "/analytics/summary", {
            method: "GET",
            credentials: "include"
        });

        const text = await response.text();
        console.log("Dashboard summary response:", text);

        const result = JSON.parse(text);

        if (result.status && result.data) {
            document.getElementById("totalAlumni").textContent = result.data.total_alumni || 0;
            document.getElementById("totalProgrammes").textContent = result.data.total_programmes || 0;
            document.getElementById("totalSectors").textContent = result.data.total_sectors || 0;
            document.getElementById("totalCertifications").textContent = result.data.total_certifications || 0;
        }
    } catch (error) {
        console.error("Dashboard summary error:", error);
    }
}

protectPage();
loadDashboardSummary();