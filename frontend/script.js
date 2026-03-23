// === API Configuration ===
const API_URL = '../Ticket_inventory-system/index.php';
let currentUser = null;

// === Initialization ===
function init() {
  const user = localStorage.getItem('user');
  const token = localStorage.getItem('token');
  
  if (user && token) {
    currentUser = JSON.parse(user);
    document.getElementById('loginSection').style.display = 'none';
    document.getElementById('appSection').style.display = 'block';
    document.getElementById('userNameDisplay').textContent = `👤 ${currentUser.name} (${currentUser.role})`;
    setupNavigation();
  } else {
    document.getElementById('loginSection').style.display = 'flex';
    document.getElementById('appSection').style.display = 'none';
  }
}

async function fetchAuth(url, options = {}) {
  const token = localStorage.getItem('token');
  const headers = { 'Content-Type': 'application/json' };
  if (token) headers['Authorization'] = `Bearer ${token}`;
  
  const res = await fetch(`${API_URL}${url}`, { ...options, headers });
  if (res.status === 401) { logout(); throw new Error('Unauthorized'); }
  return res.json();
}

// === Authentication ===
async function login() {
  const name = document.getElementById('loginName').value;
  const email = document.getElementById('loginEmail').value;
  const errorEl = document.getElementById('loginError');
  
  if (!name || !email) return errorEl.textContent = 'Name and Email are required';
  errorEl.textContent = 'Logging in...';

  try {
    const res = await fetch(`${API_URL}/auth/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name, email })
    });
    const result = await res.json();
    
    if (result.success) {
      localStorage.setItem('token', result.token);
      localStorage.setItem('user', JSON.stringify(result.user));
      errorEl.textContent = '';
      init();
    } else {
      errorEl.textContent = result.message || 'Login failed';
    }
  } catch (err) {
    errorEl.textContent = 'Network error during login';
  }
}

function logout() {
  localStorage.removeItem('token');
  localStorage.removeItem('user');
  currentUser = null;
  init();
}

// === Navigation & Roles ===
function setupNavigation() {
  const nav = document.getElementById('mainNav');
  nav.innerHTML = ''; // clear

  if (currentUser.role === 'employee') {
    nav.innerHTML = `<button class="active" onclick="showView('employeeView')">👨‍💼 My Portal</button>`;
    showView('employeeView');
    loadEmployeeDash();
  } else if (currentUser.role === 'support_agent') {
    nav.innerHTML = `<button class="active" onclick="showView('supportView')">🛠️ Support Desk</button>`;
    showView('supportView');
    loadSupportDash();
  } else if (currentUser.role === 'inventory_manager') {
    nav.innerHTML = `<button class="active" onclick="showView('managerView')">📦 Inventory HQ</button>`;
    showView('managerView');
    loadManagerDash();
  }
}

function showView(viewId) {
  document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
  document.getElementById(viewId).classList.add('active');
}

// ==============================
// EMPLOYEE LOGIC
// ==============================
async function loadEmployeeDash() {
  try {
    const [ticketsRes, itemsRes, requestsRes] = await Promise.all([
      fetchAuth('/tickets'),
      fetchAuth('/inventory'),
      fetchAuth('/requests')
    ]);

    // Populate Item Dropdown
    if (itemsRes.success) {
      const select = document.getElementById('empItemSelect');
      select.innerHTML = itemsRes.inventory.map(i => `<option value="${i.id}">${i.name} (Stock: ${i.stock})</option>`).join('');
    }

    // Populate Tickets
    if (ticketsRes.success) {
      document.getElementById('empTicketsTable').innerHTML = ticketsRes.tickets.map(t => `
        <tr>
          <td>#${t.id}</td>
          <td>${t.title}</td>
          <td>${t.priority}</td>
          <td><span class="badge badge-${t.status.toLowerCase()}">${t.status}</span></td>
          <td>${t.tech_name || 'Unassigned'}</td>
        </tr>
      `).join('');
    }

    // Populate Requests
    if (requestsRes.success) {
      document.getElementById('empRequestsTable').innerHTML = requestsRes.requests.map(r => `
        <tr>
          <td>#${r.id}</td>
          <td>${r.item_name}</td>
          <td>${r.quantity}</td>
          <td><span class="badge badge-${r.status.toLowerCase()}">${r.status}</span></td>
        </tr>
      `).join('');
    }
  } catch (err) { console.error(err); }
}

async function submitTicket() {
  const title = document.getElementById('empTicketTitle').value;
  const desc = document.getElementById('empTicketDesc').value;
  const pri = document.getElementById('empTicketPriority').value;
  if (!title) return alert('Title is required');

  const res = await fetchAuth('/tickets', {
    method: 'POST',
    body: JSON.stringify({ title, description: desc, priority: pri })
  });
  if (res.success) {
    document.getElementById('empTicketTitle').value = '';
    document.getElementById('empTicketDesc').value = '';
    loadEmployeeDash();
  } else alert(res.message);
}

async function requestItem() {
  const item_id = document.getElementById('empItemSelect').value;
  const qty = document.getElementById('empItemQty').value;
  
  const res = await fetchAuth('/requests', {
    method: 'POST',
    body: JSON.stringify({ item_id, quantity: qty })
  });
  if (res.success) {
    document.getElementById('empItemQty').value = '1';
    loadEmployeeDash();
  } else alert(res.message);
}

// ==============================
// SUPPORT AGENT LOGIC
// ==============================
async function loadSupportDash() {
  try {
    const res = await fetchAuth('/tickets');
    if (res.success) {
      document.getElementById('supportTicketsTable').innerHTML = res.tickets.map(t => `
        <tr>
          <td>#${t.id}</td>
          <td>${t.requester_name}</td>
          <td>${t.title}</td>
          <td>${t.priority}</td>
          <td><span class="badge badge-${t.status.toLowerCase()}">${t.status}</span></td>
          <td>${t.tech_name || 'Unassigned'}</td>
          <td>
            ${t.status === 'Open' ? `<button class="action-btn btn-success" onclick="actOnTicket(${t.id}, 'resolve')">Resolve</button>
            <button class="action-btn btn-danger" onclick="actOnTicket(${t.id}, 'refuse')">Refuse</button>
            <button class="action-btn btn-primary" onclick="assignTech(${t.id})">Take It</button>` : ''}
          </td>
        </tr>
      `).join('');
    }
  } catch (err) { console.error(err); }
}

async function actOnTicket(id, action) {
  const res = await fetchAuth(`/tickets/${id}/${action}`, { method: 'POST' });
  if (res.success) loadSupportDash(); else alert(res.message);
}

async function assignTech(id) {
  // Simple assign to self for MVP
  const res = await fetchAuth(`/tickets/${id}/assign`, {
    method: 'POST',
    body: JSON.stringify({ tech_id: currentUser.id })
  });
  if (res.success) loadSupportDash(); else alert(res.message);
}

// ==============================
// INVENTORY MANAGER LOGIC
// ==============================
async function loadManagerDash() {
  try {
    const [invRes, reqRes] = await Promise.all([
      fetchAuth('/inventory'),
      fetchAuth('/requests')
    ]);

    if (invRes.success) {
      document.getElementById('mgrInventoryTable').innerHTML = invRes.inventory.map(i => `
        <tr>
          <td>${i.name}</td>
          <td><strong>${i.stock}</strong></td>
          <td>${i.entering}</td>
          <td>${i.outgoing}</td>
          <td>${i.quality}</td>
          <td><span class="badge ${i.stock <= i.threshold ? 'badge-low' : 'badge-ok'}">${i.stock <= i.threshold ? 'Low Stock' : 'Healthy'}</span></td>
        </tr>
      `).join('');
    }

    if (reqRes.success) {
      const pending = reqRes.requests.filter(r => r.status === 'Pending');
      document.getElementById('mgrDemandsTable').innerHTML = pending.length ? pending.map(r => `
        <tr>
          <td>${r.employee_name}</td>
          <td>${r.item_name}</td>
          <td>${r.quantity}</td>
          <td>
            <button class="action-btn btn-success" onclick="actOnRequest(${r.id}, 'validate')">✓</button>
            <button class="action-btn btn-danger" onclick="actOnRequest(${r.id}, 'refuse')">✕</button>
          </td>
        </tr>
      `).join('') : '<tr><td colspan="4">No pending demands.</td></tr>';
    }
  } catch (err) { console.error(err); }
}

async function addNewItem() {
  const name = document.getElementById('mgrItemName').value;
  const stock = document.getElementById('mgrItemStock').value;
  if (!name) return alert('Name required');

  const res = await fetchAuth('/inventory', {
    method: 'POST',
    body: JSON.stringify({ name, stock, entering: stock, threshold: 5, quality: 'Good' })
  });
  if (res.success) {
    document.getElementById('mgrItemName').value = '';
    document.getElementById('mgrItemStock').value = '';
    loadManagerDash();
  } else alert(res.message);
}

async function actOnRequest(id, action) {
  const res = await fetchAuth(`/requests/${id}/${action}`, { method: 'POST' });
  if (res.success) loadManagerDash(); else alert(res.message);
}

// Boot up
init();