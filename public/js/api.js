// API Helper for ApexAcademy

const API = {
    async request(url, options = {}) {
        try {
            const response = await fetch(url, options);
            const contentType = response.headers.get("content-type");
            
            let data;
            if (contentType && contentType.includes("application/json")) {
                data = await response.json();
            } else {
                data = { success: false, error: await response.text() };
            }

            if (!response.ok) {
                throw new Error(data.error || `HTTP error! status: ${response.status}`);
            }

            return data;
        } catch (error) {
            console.error("API Request Error:", error);
            return { success: false, error: error.message };
        }
    },

    async get(url) {
        return this.request(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
    },

    async post(url, data = {}) {
        return this.request(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        });
    }
};
