function protectBiddingPage() {
    const user = localStorage.getItem("loggedInUser");

    if (!user) {
        window.location.href = "login.html";
    }
}

function showBidMessage(text, type = "success") {
    const box = document.getElementById("bidMessage");
    box.textContent = text;
    box.className = "message " + type;
}

async function placeBid() {
    const bidDate = document.getElementById("bidDate").value;
    const bidAmount = document.getElementById("bidAmount").value;

    try {
        const response = await fetch(API_BASE_URL + "/bids", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            credentials: "include",
            body: JSON.stringify({
                bid_date: bidDate,
                bid_amount: bidAmount
            })
        });

        const result = await response.json();

        if (result.status) {
            showBidMessage(result.message, "success");
            loadBidStatus();
        } else {
            showBidMessage(result.message, "error");
        }
    } catch (error) {
        showBidMessage("Could not place bid. Check API connection.", "error");
        console.error(error);
    }
}

async function updateBid() {
    const bidId = document.getElementById("updateBidId").value;
    const bidAmount = document.getElementById("updateBidAmount").value;

    try {
        const response = await fetch(API_BASE_URL + "/bids/" + bidId, {
            method: "PUT",
            headers: {
                "Content-Type": "application/json"
            },
            credentials: "include",
            body: JSON.stringify({
                bid_amount: bidAmount
            })
        });

        const result = await response.json();

        if (result.status) {
            showBidMessage(result.message, "success");
            loadBidStatus();
        } else {
            showBidMessage(result.message, "error");
        }
    } catch (error) {
        showBidMessage("Could not update bid. Check API connection.", "error");
        console.error(error);
    }
}

async function loadBidStatus() {
    const output = document.getElementById("bidStatusOutput");

    output.innerHTML = "<p>Loading bid status...</p>";

    try {
        const response = await fetch(API_BASE_URL + "/bids/status", {
            method: "GET",
            credentials: "include"
        });

        const result = await response.json();

        if (!result.status || !result.data || !result.data.bids || result.data.bids.length === 0) {
            output.innerHTML = "<p>No bids found.</p>";
            return;
        }

        let html = `
            <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Bid ID</th>
                        <th>Date</th>
                        <th>Your Amount</th>
                        <th>Status</th>
                        <th>Blind Status</th>
                    </tr>
                </thead>
                <tbody>
        `;

        result.data.bids.forEach(function (bid) {
            html += `
                <tr>
                    <td>${bid.id}</td>
                    <td>${bid.bid_date}</td>
                    <td>${bid.your_bid_amount}</td>
                    <td>${bid.status}</td>
                    <td>${bid.blind_status}</td>
                </tr>
            `;
        });

        html += `
                </tbody>
            </table>
            </div>
        `;

        output.innerHTML = html;
    } catch (error) {
        output.innerHTML = "<p>Could not load bid status.</p>";
        console.error(error);
    }
}

async function loadRemainingSlots() {
    const output = document.getElementById("slotsOutput");

    output.innerHTML = "<p>Checking slots...</p>";

    try {
        const response = await fetch(API_BASE_URL + "/bids/remaining-slots", {
            method: "GET",
            credentials: "include"
        });

        const result = await response.json();

        if (result.status && result.data) {
            output.innerHTML = `
                <p><strong>Monthly Limit:</strong> ${result.data.monthly_limit}</p>
                <p><strong>Used Slots:</strong> ${result.data.used_slots}</p>
                <p><strong>Remaining Slots:</strong> ${result.data.remaining_slots}</p>
            `;
        } else {
            output.innerHTML = "<p>Could not load remaining slots.</p>";
        }
    } catch (error) {
        output.innerHTML = "<p>Could not load remaining slots.</p>";
        console.error(error);
    }
}

protectBiddingPage();
loadBidStatus();
loadRemainingSlots();