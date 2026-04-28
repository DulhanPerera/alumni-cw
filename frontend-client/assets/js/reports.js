let currentReportData = [];

function protectReportsPage() {
    const user = localStorage.getItem("loggedInUser");

    if (!user) {
        window.location.href = "login.html";
    }
}

function buildReportUrl() {
    const programme = document.getElementById("reportProgrammeFilter").value.trim();
    const graduationYear = document.getElementById("reportGraduationYearFilter").value.trim();
    const industrySector = document.getElementById("reportIndustryFilter").value.trim();

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

    let url = API_BASE_URL + "/alumni";

    if (params.toString() !== "") {
        url += "?" + params.toString();
    }

    return url;
}

async function generateReport() {
    const reportOutput = document.getElementById("reportOutput");

    reportOutput.innerHTML = "<p>Generating report...</p>";

    try {
        const response = await fetch(buildReportUrl(), {
            method: "GET",
            credentials: "include"
        });

        const text = await response.text();
        const result = JSON.parse(text);

        if (!result.status || !result.data || result.data.length === 0) {
            currentReportData = [];
            reportOutput.innerHTML = "<p>No data found for the selected filters.</p>";
            return;
        }

        currentReportData = result.data;

        const totalAlumni = currentReportData.length;

        const programmes = new Set();
        const sectors = new Set();
        const companies = new Set();
        const jobTitles = new Set();

        currentReportData.forEach(function (item) {
            if (item.programme || item.degree_name) {
                programmes.add(item.programme || item.degree_name);
            }

            if (item.industry_sector) {
                sectors.add(item.industry_sector);
            }

            if (item.company_name || item.current_company) {
                companies.add(item.company_name || item.current_company);
            }

            if (item.job_title || item.current_job_title) {
                jobTitles.add(item.job_title || item.current_job_title);
            }
        });

        reportOutput.innerHTML = `
            <p><strong>Total Alumni:</strong> ${totalAlumni}</p>
            <p><strong>Programmes Found:</strong> ${programmes.size}</p>
            <p><strong>Industry Sectors Found:</strong> ${sectors.size}</p>
            <p><strong>Companies Found:</strong> ${companies.size}</p>
            <p><strong>Job Titles Found:</strong> ${jobTitles.size}</p>

            <br>

            <h3>Interpretation</h3>
            <p>
                The selected alumni data can help the university identify employment trends,
                common career pathways, and professional development patterns.
            </p>
        `;
    } catch (error) {
        reportOutput.innerHTML = "<p>Could not generate report. Check API connection.</p>";
        console.error(error);
    }
}

function exportReportAsCSV() {
    if (currentReportData.length === 0) {
        alert("Please generate a report first.");
        return;
    }

    const headers = [
        "Name",
        "Email",
        "Programme",
        "Graduation Year",
        "Job Title",
        "Company",
        "Industry Sector"
    ];

    const rows = currentReportData.map(function (item) {
        return [
            item.full_name || item.name || "",
            item.email || "",
            item.programme || item.degree_name || "",
            item.graduation_year || item.completion_date || "",
            item.job_title || item.current_job_title || "",
            item.company_name || item.current_company || "",
            item.industry_sector || ""
        ];
    });

    let csvContent = headers.join(",") + "\n";

    rows.forEach(function (row) {
        const cleanRow = row.map(function (value) {
            return '"' + String(value).replace(/"/g, '""') + '"';
        });

        csvContent += cleanRow.join(",") + "\n";
    });

    const blob = new Blob([csvContent], {
        type: "text/csv;charset=utf-8;"
    });

    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");

    link.setAttribute("href", url);
    link.setAttribute("download", "alumni_report.csv");
    link.style.display = "none";

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}


protectReportsPage();