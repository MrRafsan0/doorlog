/**
 * DoorLog - Unified JS
 * POST/GET/SEARCH: fully public
 * DELETE: admin only (enforced server-side)
 */

document.addEventListener('DOMContentLoaded', () => {

    // ── 1. ADD NEW LOCATION (public) ─────────────────────────────
    const locationForm = document.getElementById('locationForm');
    if (locationForm) {
        locationForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const address   = document.getElementById('address').value.trim();
            const door_code = document.getElementById('doorCode').value.trim();

            try {
                const response = await fetch('api.php', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify({ address, door_code, action: 'check' })
                });

                const data = await response.json();
                const msg  = document.getElementById('successMsg');

                if (response.status === 429 || data.error === 'capacity_full') {
                    alert("⚠️ Demo database is full (50/50 entries).\nPlease try again later.");
                    return;
                }

                if (response.ok) {
                    if (data.status === 'exists_exact') {
                        alert(`Already logged!\n\nAddress: ${data.address}\nCode: ${data.door_code}`);

                    } else if (data.status === 'exists_different') {
                        const doUpdate = confirm(
                            `This address already exists!\n\nOld Code: ${data.old_code}\nNew Code: ${data.new_code}\n\nUpdate it?`
                        );
                        if (doUpdate) {
                            const updateRes = await fetch('api.php', {
                                method:  'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body:    JSON.stringify({ address, door_code, action: 'update' })
                            });
                            if (updateRes.ok) {
                                showMsg(msg, "Code updated successfully!");
                                locationForm.reset();
                                refreshCounter();
                            }
                        }
                    } else if (data.status === 'inserted') {
                        showMsg(msg, "Location logged successfully!");
                        locationForm.reset();
                        refreshCounter();
                    }
                }
            } catch (err) {
                console.error("Save failed:", err);
            }
        });
    }

    // ── 2. ADMIN LOGIN ───────────────────────────────────────────
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            try {
                const response = await fetch('auth.php', {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify({ username, password })
                });

                if (response.ok) {
                    window.location.href = 'search';
                } else {
                    document.getElementById('errorMsg').innerText = "Incorrect credentials.";
                }
            } catch (err) {
                console.error("Auth failed:", err);
            }
        });
    }

    // ── 3. SEARCH (public) + DELETE (admin only server-side) ─────
    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
        const resultsDiv = document.getElementById('searchResults');

        const handleDelete = async (id, address, entryEl) => {
            if (!confirm(`Delete this entry?\n\n"${address}"\n\nThis cannot be undone.`)) return;

            try {
                const response = await fetch('api.php', {
                    method:  'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body:    JSON.stringify({ id })
                });

                if (response.status === 401) {
                    alert("You need to be logged in as admin to delete entries.");
                    return;
                }

                if (response.ok) {
                    entryEl.style.transition = 'all 0.3s ease';
                    entryEl.style.opacity    = '0';
                    entryEl.style.transform  = 'translateX(30px)';
                    setTimeout(() => entryEl.remove(), 300);
                } else {
                    const data = await response.json();
                    alert(`Delete failed: ${data.error || 'Unknown error'}`);
                }
            } catch (err) {
                console.error("Delete failed:", err);
            }
        };

        const fetchLogs = async (searchTerm = '') => {
            resultsDiv.innerHTML = '<p style="text-align:center;color:#64748b;">Loading...</p>';

            const url      = searchTerm ? `api.php?search=${encodeURIComponent(searchTerm)}` : 'api.php';
            const response = await fetch(url);
            const data     = await response.json();
            const entries  = data.entries || data;

            resultsDiv.innerHTML = '';

            if (!entries.length) {
                resultsDiv.innerHTML = '<p style="text-align:center;color:#64748b;">No entries found.</p>';
                return;
            }

            // Check if logged in to decide whether to show delete buttons
            let isAdmin = false;
            try {
                const sessionRes = await fetch('me.php');
                const sessionData = await sessionRes.json();
                isAdmin = sessionData.logged_in;
            } catch (e) {}

            entries.forEach(item => {
                const entry = document.createElement('div');
                entry.className = 'log-entry';
                entry.innerHTML = `
                    <div class="log-entry-info">
                        <strong>Address:</strong> ${item.address}<br>
                        <strong>Door Code:</strong> ${item.door_code}
                    </div>
                    ${isAdmin ? `<button class="btn-delete" title="Delete">🗑 Delete</button>` : ''}
                `;
                if (isAdmin) {
                    entry.querySelector('.btn-delete').addEventListener('click', () => {
                        handleDelete(item.id, item.address, entry);
                    });
                }
                resultsDiv.appendChild(entry);
            });
        };

        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            fetchLogs(document.getElementById('searchInput').value);
        });

        fetchLogs();
    }
});

// ── Helpers ──────────────────────────────────────────────────────
function showMsg(el, text) {
    el.innerText = text;
    el.style.display = 'block';
    setTimeout(() => { el.style.display = 'none'; }, 3000);
}

function refreshCounter() {
    fetch('api.php')
        .then(r => r.json())
        .then(data => {
            const counter = document.getElementById('entryCounter');
            if (counter && data.count !== undefined) {
                counter.innerText = `${data.count}/50 entries · ${data.remaining} slots remaining`;
            }
        })
        .catch(() => {});
}