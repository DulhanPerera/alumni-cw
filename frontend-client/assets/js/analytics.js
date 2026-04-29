// Analytics page scripts.
let charts = {};

function protectAnalyticsPage() {
    const user = localStorage.getItem("loggedInUser");

    if (!user) {
        window.location.href = "login.html";
    }
}

function getAnalyticsQueryString() {
    const programme = document.getElementById("analyticsProgrammeFilter").value.trim();
    const graduationYear = document.getElementById("analyticsGraduationYearFilter").value.trim();
    const industrySector = document.getElementById("analyticsIndustryFilter").value.trim();

    const params = new URLSearchParams();

    if (programme !== "") {
        params.append("programme", programme);
    }

    if (graduationYear !== "") {
        params.append("graduation_year", graduationYear);
    }

    if (industrySector !== "") {
        params.append("industry_sector", industrySector);
    }

    return params.toString();
}

async function fetchAnalytics(endpoint) {
    let url = API_BASE_URL + endpoint;
    const query = getAnalyticsQueryString();

    if (query !== "") {
        url += "?" + query;
    }

    console.log("Analytics URL:", url);

    const response = await fetch(url, {
        method: "GET",
        headers: {
            "Authorization": "Bearer " + ANALYTICS_API_KEY
        },
        credentials: "include"
    });

    const text = await response.text();
    return JSON.parse(text);
}

function drawChart(chartId, type, labels, values, labelText) {
    const canvas = document.getElementById(chartId);

    if (charts[chartId]) {
        charts[chartId].destroy();
    }

    charts[chartId] = new Chart(canvas, {
        type: type,
        data: {
            labels: labels,
            datasets: [
                {
                    label: labelText,
                    data: values
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true
                },
                tooltip: {
                    enabled: true
                }
            },
            scales: type === "pie" || type === "doughnut" ? {} : {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function normaliseChartData(data, labelField, valueField) {
    const labels = [];
    const values = [];

    data.forEach(function (item) {
        labels.push(item[labelField] || "Unknown");
        values.push(Number(item[valueField]) || 0);
    });

    return {
        labels: labels,
        values: values
    };
}

async function loadProgrammeChart() {
    try {
        const result = await fetchAnalytics("/analytics/alumni-by-programme");

        if (result.status && result.data) {
            const chartData = normaliseChartData(result.data, "programme", "total");
            drawChart("programmeChart", "bar", chartData.labels, chartData.values, "Alumni Count");
        }
    } catch (error) {
        console.error("Programme chart error:", error);
    }
}

async function loadSectorChart() {
    try {
        const result = await fetchAnalytics("/analytics/employment-by-sector");

        if (result.status && result.data) {
            const chartData = normaliseChartData(result.data, "industry_sector", "total");
            drawChart("sectorChart", "doughnut", chartData.labels, chartData.values, "Industry Sector");
        }
    } catch (error) {
        console.error("Sector chart error:", error);
    }
}

async function loadJobTitleChart() {
    try {
        const result = await fetchAnalytics("/analytics/top-job-titles");

        if (result.status && result.data) {
            const chartData = normaliseChartData(result.data, "job_title", "total");
            drawChart("jobTitleChart", "bar", chartData.labels, chartData.values, "Job Titles");
        }
    } catch (error) {
        console.error("Job title chart error:", error);
    }
}

async function loadEmployerChart() {
    try {
        const result = await fetchAnalytics("/analytics/top-employers");

        if (result.status && result.data) {
            const chartData = normaliseChartData(result.data, "company_name", "total");
            drawChart("employerChart", "bar", chartData.labels, chartData.values, "Employers");
        }
    } catch (error) {
        console.error("Employer chart error:", error);
    }
}

async function loadCertificationGrowthChart() {
    try {
        const result = await fetchAnalytics("/analytics/certification-growth");

        if (result.status && result.data) {
            const chartData = normaliseChartData(result.data, "year", "total");
            drawChart("certificationGrowthChart", "line", chartData.labels, chartData.values, "Certifications");
        }
    } catch (error) {
        console.error("Certification growth chart error:", error);
    }
}

async function loadLocationChart() {
    try {
        const result = await fetchAnalytics("/analytics/geographic-distribution");

        if (result.status && result.data) {
            const chartData = normaliseChartData(result.data, "location", "total");
            drawChart("locationChart", "pie", chartData.labels, chartData.values, "Locations");
        }
    } catch (error) {
        console.error("Location chart error:", error);
    }
}

function loadAllCharts() {
    loadProgrammeChart();
    loadSectorChart();
    loadJobTitleChart();
    loadEmployerChart();
    loadCertificationGrowthChart();
    loadLocationChart();
}

protectAnalyticsPage();
loadAllCharts();