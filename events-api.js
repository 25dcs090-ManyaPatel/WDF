
const apiURL = "api/events.php";

const form = document.getElementById("eventForm");
const eventList = document.getElementById("eventList");
const message = document.getElementById("message");

function showMessage(text, success = true) {
    message.textContent = text;
    message.style.color = success ? "green" : "red";
}

async function requestAPI(method, data = null) {
    const options = { method, headers: {} };

    if (data !== null) {
        options.headers["Content-Type"] = "application/json";
        options.body = JSON.stringify(data);
    }

    const response = await fetch(apiURL, options);
    const result = await response.json();

    if (!response.ok || !result.success) {
        throw new Error(result.message || "Request failed.");
    }

    return result;
}

async function loadEvents() {
    eventList.textContent = "Loading events...";

    try {
        const result = await requestAPI("GET");
        eventList.replaceChildren();

        if (!result.data.length) {
            eventList.textContent = "No events found.";
            return;
        }

        result.data.forEach(event => {
            const card = document.createElement("article");
            card.className = "event-item";

            const title = document.createElement("h3");
            title.textContent = event.title;

            const details = document.createElement("p");
            details.textContent =
                `${event.category} | ${event.event_date} | ` +
                `${event.location} | ${event.status}`;

            const description = document.createElement("p");
            description.textContent = event.description;

            const editButton = document.createElement("button");
            editButton.textContent = "Edit";
            editButton.onclick = () => editEvent(event);

            const deleteButton = document.createElement("button");
            deleteButton.textContent = "Delete";
            deleteButton.onclick = () => deleteEvent(event.event_id);

            card.append(title, details, description, editButton, deleteButton);
            eventList.appendChild(card);
        });
    } catch (error) {
        eventList.textContent = "Could not load events.";
        showMessage(error.message, false);
    }
}

function editEvent(event) {
    document.getElementById("event_id").value = event.event_id;
    document.getElementById("title").value = event.title;
    document.getElementById("category").value = event.category;
    document.getElementById("event_date").value = event.event_date;
    document.getElementById("location").value = event.location;
    document.getElementById("description").value = event.description;
    document.getElementById("status").value = event.status;

    window.scrollTo({ top: 0, behavior: "smooth" });
}

form.addEventListener("submit", async event => {
    event.preventDefault();

    const id = document.getElementById("event_id").value;

    const data = {
        title: document.getElementById("title").value.trim(),
        category: document.getElementById("category").value.trim(),
        event_date: document.getElementById("event_date").value,
        location: document.getElementById("location").value.trim(),
        description: document.getElementById("description").value.trim(),
        status: document.getElementById("status").value
    };

    if (id) data.event_id = Number(id);

    try {
        const result = await requestAPI(id ? "PUT" : "POST", data);
        showMessage(result.message);
        form.reset();
        document.getElementById("event_id").value = "";
        await loadEvents();
    } catch (error) {
        showMessage(error.message, false);
    }
});

async function deleteEvent(id) {
    if (!confirm("Are you sure you want to delete this event?")) return;

    try {
        const result = await requestAPI("DELETE", { event_id: Number(id) });
        showMessage(result.message);
        await loadEvents();
    } catch (error) {
        showMessage(error.message, false);
    }
}

document.getElementById("clearBtn").addEventListener("click", () => {
    form.reset();
    document.getElementById("event_id").value = "";
    showMessage("");
});

loadEvents();
