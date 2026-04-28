// Alumni page scripts.
function protectAlumniPage() {
    const user = localStorage.getItem("loggedInUser");

    if (!user) {
        window.location.href = "login.html";
    }
}

function buildAlumniUrl() {
    const programme = document.getElementById("programmeFilter").value.trim();
    const graduationYear = document.getElementById("graduationYearFilter").value.trim();
    const industrySector = document.getElementById("industrySectorFilter").value.trim();

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

async function loadAlumni() {
    const tableBody = document.getElementById("alumniTableBody");

    tableBody.innerHTML = `
        <tr>
            <td colspan="7" class="empty-message">Loading alumni records...</td>
        </tr>
    `;

    try {
        const response = await fetch(buildAlumniUrl(), {
            method: "GET",
            credentials: "include"
        });

        const text = await response.text();
        const result = JSON.parse(text);

        if (!result.status || !result.data || result.data.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="empty-message">No alumni records found.</td>
                </tr>
            `;
            return;
        }

        tableBody.innerHTML = "";

        result.data.forEach(function (alumnus) {
            const row = document.createElement("tr");

            row.innerHTML = `
                <td>${alumnus.full_name || alumnus.name || "-"}</td>
                <td>${alumnus.email || "-"}</td>
                <td>${alumnus.programme || alumnus.degree_name || "-"}</td>
                <td>${alumnus.graduation_year || alumnus.completion_date || "-"}</td>
                <td>${alumnus.job_title || alumnus.current_job_title || "-"}</td>
                <td>${alumnus.company_name || alumnus.current_company || "-"}</td>
                <td>${alumnus.industry_sector || "-"}</td>
            `;

            tableBody.appendChild(row);
        });
    } catch (error) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="7" class="empty-message">Could not load alumni data. Check API connection.</td>
            </tr>
        `;

        console.error(error);
    }
}

protectAlumniPage();
loadAlumni();