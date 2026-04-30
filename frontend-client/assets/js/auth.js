/*
   Name - Dulhan Perera
   IIT ID - 20210165
   UoW ID - w1912842
*/


// Authentication form handlers for login, registration, and resets.

function showMessage(text, type = "success") {
    const messageBox = document.getElementById("message");

    if (!messageBox) {
        return;
    }

    messageBox.textContent = text;
    messageBox.className = "message " + type;
}

async function sendRequest(url, method, data = null) {
    const options = {
        method: method,
        headers: {
            "Content-Type": "application/json"
        },
        credentials: "include"
    };

    if (data !== null) {
        options.body = JSON.stringify(data);
    }

    const response = await fetch(url, options);
    const text = await response.text();

    console.log("API URL:", url);
    console.log("HTTP Status:", response.status);
    console.log("Raw API Response:", text);

    try {
        return JSON.parse(text);
    } catch (error) {
        throw new Error("API did not return valid JSON: " + text);
    }
}

const registerForm = document.getElementById("registerForm");

if (registerForm) {
    registerForm.addEventListener("submit", async function (event) {
        event.preventDefault();

        const fullName = document.getElementById("full_name").value.trim();
        const email = document.getElementById("email").value.trim();
        const password = document.getElementById("password").value;

        try {
            const result = await sendRequest(API_BASE_URL + "/auth/register", "POST", {
                full_name: fullName,
                email: email,
                password: password
            });

            if (result.status) {
                showMessage(result.message, "success");

                if (result.data && result.data.verify_url) {
                    const messageBox = document.getElementById("message");

                    messageBox.innerHTML =
                        result.message +
                        "<br><br><a href='" + result.data.verify_url + "' target='_blank'>Click here to verify email</a>";

                    console.log("Verification URL:", result.data.verify_url);
                }
            } else {
                showMessage(result.message, "error");
            }
        } catch (error) {
            showMessage("Registration failed. Please check the API connection.", "error");
        }
    });
}

const loginForm = document.getElementById("loginForm");

if (loginForm) {
    loginForm.addEventListener("submit", async function (event) {
        event.preventDefault();

        const email = document.getElementById("email").value.trim();
        const password = document.getElementById("password").value;

        try {
            const result = await sendRequest(API_BASE_URL + "/auth/login", "POST", {
                email: email,
                password: password
            });

            if (result.status) {
                localStorage.setItem("loggedInUser", JSON.stringify(result.data));
                showMessage("Login successful. Redirecting...", "success");

                setTimeout(function () {
                    window.location.href = "dashboard.html";
                }, 1000);
            } else {
                showMessage(result.message, "error");
            }
        } catch (error) {
            showMessage("Login failed. Please check the API connection.", "error");
        }
    });
}

const forgotPasswordForm = document.getElementById("forgotPasswordForm");

if (forgotPasswordForm) {
    forgotPasswordForm.addEventListener("submit", async function (event) {
        event.preventDefault();

        const email = document.getElementById("email").value.trim();

        try {
            const result = await sendRequest(API_BASE_URL + "/auth/forgot-password", "POST", {
                email: email
            });

            if (result.status) {
                showMessage(result.message, "success");

                if (result.data && result.data.reset_token) {
                    const messageBox = document.getElementById("message");

                    messageBox.innerHTML =
                        result.message +
                        "<br><br><strong>Reset Token:</strong><br>" +
                        result.data.reset_token +
                        "<br><br><a href='reset-password.html?token=" +
                        result.data.reset_token +
                        "'>Go to reset password page</a>";
                }
            } else {
                showMessage(result.message, "error");
            }
        } catch (error) {
            showMessage("Password reset request failed. Please check the API connection.", "error");
        }
    });
}

const resetPasswordForm = document.getElementById("resetPasswordForm");

if (resetPasswordForm) {
    const urlParams = new URLSearchParams(window.location.search);
    const tokenFromUrl = urlParams.get("token");

    if (tokenFromUrl) {
        document.getElementById("token").value = tokenFromUrl;
    }

    resetPasswordForm.addEventListener("submit", async function (event) {
        event.preventDefault();

        const token = document.getElementById("token").value.trim();
        const newPassword = document.getElementById("new_password").value;

        try {
            const result = await sendRequest(API_BASE_URL + "/auth/reset-password", "POST", {
                token: token,
                new_password: newPassword
            });

            if (result.status) {
                showMessage("Password reset successful. You can now login.", "success");

                setTimeout(function () {
                    window.location.href = "login.html";
                }, 1500);
            } else {
                showMessage(result.message, "error");
            }
        } catch (error) {
            showMessage("Password reset failed. Please check the API connection.", "error");
        }
    });
}

if (window.location.pathname.includes("verify-email.html")) {
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get("token");

    if (!token) {
        showMessage("Verification token is missing.", "error");
    } else {
        verifyEmail(token);
    }
}

async function verifyEmail(token) {
    try {
        const result = await sendRequest(API_BASE_URL + "/auth/verify-email?token=" + token, "GET");

        if (result.status) {
            showMessage(result.message, "success");
            document.getElementById("verifyText").textContent = "Your email has been verified.";
        } else {
            showMessage(result.message, "error");
            document.getElementById("verifyText").textContent = "Email verification failed.";
        }
    } catch (error) {
        showMessage("Verification failed. Please check the API connection.", "error");
    }
}

function logout() {
    fetch(API_BASE_URL + "/auth/logout", {
        method: "POST",
        credentials: "include"
    })
    .then(function () {
        localStorage.removeItem("loggedInUser");
        window.location.href = "login.html";
    })
    .catch(function () {
        localStorage.removeItem("loggedInUser");
        window.location.href = "login.html";
    });
}