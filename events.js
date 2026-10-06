let allEvents = [];
let filteredEvents = [];
let currentPage = 1;

const eventsPerPage = 6;

const eventContainer = document.getElementById("eventContainer");
const searchInput = document.getElementById("searchInput");
const categoryFilter = document.getElementById("categoryFilter");
const sortSelect = document.getElementById("sortSelect");
const loading = document.getElementById("loading");
const errorMessage = document.getElementById("errorMessage");
const noResults = document.getElementById("noResults");
const pagination = document.getElementById("pagination");


async function loadEvents() {

    try {

        loading.style.display = "flex";
        errorMessage.style.display = "none";

        const response = await fetch("./event.json");

        if (!response.ok) {
            throw new Error("event.json not found");
        }

        const data = await response.json();

        console.log("JSON loaded:", data);

        allEvents = data;
        filteredEvents = [...data];

        loading.style.display = "none";

        displayEvents(filteredEvents);

    } catch (error) {

        console.error("ERROR:", error);

        loading.style.display = "none";
        errorMessage.style.display = "flex";

    }
}


function displayEvents(events) {

    eventContainer.innerHTML = "";

    if (events.length === 0) {

        noResults.style.display = "block";
        pagination.innerHTML = "";

        return;
    }

    noResults.style.display = "none";

    const start =
        (currentPage - 1) * eventsPerPage;

    const end =
        start + eventsPerPage;

    const pageEvents =
        events.slice(start, end);


    pageEvents.forEach(event => {

        const card =
            document.createElement("article");

        card.className = "event-card dynamic-event";

        card.innerHTML = `

            <div class="event-icon">
                <i class="fa-solid fa-calendar-check"></i>
            </div>

            <span class="event-category">
                ${event.category}
            </span>

            <h3>
                ${event.title}
            </h3>

            <p>
                <strong>Date:</strong>
                ${formatDate(event.date)}
            </p>

            <p>
                <strong>Time:</strong>
                ${event.time}
            </p>

            <p>
                <strong>Venue:</strong>
                ${event.venue}
            </p>

            <p class="event-description">
                ${event.description}
            </p>

            <button
                class="register-btn"
                onclick="registerEvent('${event.title}')">

                Register Now

            </button>

        `;

        eventContainer.appendChild(card);

    });

    createPagination(events.length);
}


function formatDate(date) {

    return new Date(date).toLocaleDateString(
        "en-IN",
        {
            day: "2-digit",
            month: "short",
            year: "numeric"
        }
    );
}


function applyFilters() {

    const search =
        searchInput.value.toLowerCase().trim();

    const category =
        categoryFilter.value;

    filteredEvents = allEvents.filter(event => {

        const searchMatch =
            event.title.toLowerCase().includes(search) ||
            event.venue.toLowerCase().includes(search) ||
            event.category.toLowerCase().includes(search);

        const categoryMatch =
            category === "all" ||
            event.category === category;

        return searchMatch && categoryMatch;
    });


    const sort = sortSelect.value;

    if (sort === "date-asc") {

        filteredEvents.sort(
            (a, b) =>
                new Date(a.date) -
                new Date(b.date)
        );

    }

    if (sort === "date-desc") {

        filteredEvents.sort(
            (a, b) =>
                new Date(b.date) -
                new Date(a.date)
        );

    }

    if (sort === "title-asc") {

        filteredEvents.sort(
            (a, b) =>
                a.title.localeCompare(b.title)
        );

    }

    if (sort === "title-desc") {

        filteredEvents.sort(
            (a, b) =>
                b.title.localeCompare(a.title)
        );

    }

    currentPage = 1;

    displayEvents(filteredEvents);
}


function createPagination(total) {

    pagination.innerHTML = "";

    const totalPages =
        Math.ceil(total / eventsPerPage);

    if (totalPages <= 1) {
        return;
    }


    for (let i = 1; i <= totalPages; i++) {

        const button =
            document.createElement("button");

        button.textContent = i;

        if (i === currentPage) {
            button.classList.add("active");
        }

        button.onclick = function () {

            currentPage = i;

            displayEvents(filteredEvents);

        };

        pagination.appendChild(button);
    }
}


function registerEvent(title) {

    alert("You selected: " + title);

}


searchInput.addEventListener(
    "input",
    applyFilters
);

categoryFilter.addEventListener(
    "change",
    applyFilters
);

sortSelect.addEventListener(
    "change",
    applyFilters
);


loadEvents();