'use strict';

const BASE_PATH = document.body.dataset.basepath || '/Artlet';
const API_BASE  = BASE_PATH + '/api/art';


const searchInput   = document.getElementById('searchInput');
const categoryInput = document.getElementById('categoryFilter');
const searchBtn     = document.getElementById('searchBtn');

function renderFilteredArts() {
    const query = (searchInput?.value || '').toLowerCase();
    const category = (categoryInput?.value || '').toLowerCase();

    const cards = document.querySelectorAll('.art-card');

    cards.forEach(card => {
        const title = (card.dataset.title || '').toLowerCase();
        const cat = (card.dataset.category || '').toLowerCase();

        const matchText = title.includes(query);
        const matchCat = !category || cat === category;

        card.style.display = (matchText && matchCat) ? 'block' : 'none';
    });
}

searchInput?.addEventListener('input', renderFilteredArts);
categoryInput?.addEventListener('change', renderFilteredArts);
searchBtn?.addEventListener('click', renderFilteredArts);


const userSearchInput = document.getElementById('userSearchInput');
const userList = document.getElementById('userList');

let users = [];

function normalizeUsers(data) {
    if (Array.isArray(data)) return data;

    return Object.values(data);
}

function fetchUsers() {
    if (!userList) return;

    fetch(BASE_PATH + '/api/users')
        .then(res => res.json())
        .then(data => {
            users = normalizeUsers(data);
            renderUsers();
        })
        .catch(err => console.error("User fetch error:", err));
}

function renderUsers(filter = "") {
    if (!userList) return;

    userList.innerHTML = "";

    const filtered = users.filter(u =>
        (u.username || "").toLowerCase().includes(filter.toLowerCase())
    );

    if (filtered.length === 0) {
        userList.innerHTML = "<p>No users found</p>";
        return;
    }

    filtered.forEach(user => {
        const div = document.createElement('div');

        div.style.padding = "15px";
        div.style.border = "1px solid #ddd";
        div.style.borderRadius = "8px";
        div.style.background = "#fff";

        div.innerHTML = `
            <h3>${user.username ?? 'Unknown'}</h3>
        `;

        userList.appendChild(div);
    });
}

userSearchInput?.addEventListener('input', (e) => {
    renderUsers(e.target.value);
});

document.addEventListener('DOMContentLoaded', () => {
    fetchUsers();
    renderFilteredArts();
});