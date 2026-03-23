// === API Configuration ===
const API_BASE = '../Ticket_inventory-system/index.php';
const API_URL  = API_BASE; // base URL; paths appended with ?path=
let currentUser = null;

// Helper: builds a URL supporting both PATH_INFO and ?path= fallback
function apiUrl(path) {
  return `${API_BASE}${path}`;
}

// === Initialization ===
function init() {
  const token = localStorage.getItem('nexus_token');
  const user = localStorage.getItem('nexus_user');
  
  if (token && user) {
    currentUser = JSON.parse(user);
    showView('appSection'); // Show app directly if logged in
    setupNavigation();
  } else {
    showView('landingView'); // Default to landing page
  }
}

// ==============================
// LANDING & LOGIN NAVIGATION
// ==============================
function navigateToLogin() {
  showView('loginSection');
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function showLanding() {
  showView('landingView');
}

function scrollToFeatures() {
  document.getElementById('features').scrollIntoView({ behavior: 'smooth' });
}

function showView(viewId) {
  // Hide all main sections
  document.getElementById('landingView').style.display = 'none';
  document.getElementById('loginSection').style.display = 'none';
  document.getElementById('appSection').style.display = 'none';
  
  // Show target
  const target = document.getElementById(viewId);
  if (target) {
     target.style.display = (viewId === 'appSection') ? 'grid' : 'flex';
     target.classList.add('active');
  }
}

async function fetchAuth(url, options = {}) {
  const token = localStorage.getItem('nexus_token');
  const headers = { 'Content-Type': 'application/json' };
  if (token) headers['Authorization'] = `Bearer ${token}`;
  
  try {
    const res = await fetch(apiUrl(url), { ...options, headers });
    let data;
    try { data = await res.json(); } catch(e) {
      throw new Error('Server returned invalid JSON. Check PHP errors.');
    }
    if (res.status === 401) { logout(); throw new Error('Unauthorized'); }
    return data; // always return data, let callers handle success:false
  } catch (err) {
    console.error('FetchAuth Error:', err);
    throw err;
  }
}

// === Authentication ===
async function login() {
  const email = document.getElementById('email').value;
  const password = document.getElementById('password').value;
  
  if (!email || !password) return showToast('Email and password are required.', 'error');

  try {
    const res = await fetch(apiUrl('/auth/login'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password })
    });
    
    // Explicitly handle PHP errors that might crash the response
    let result;
    try {
      result = await res.json();
    } catch(e) {
      return showToast('Backend Error: Please ensure you ran setup.php first!', 'error');
    }
    
    if (result.success) {
      localStorage.setItem('nexus_token', result.token);
      // Store user with id always as a number
      const usr = result.user;
      usr.id = Number(usr.id);
      localStorage.setItem('nexus_user', JSON.stringify(usr));
      showToast(result.message, 'success');
      init();
    } else {
      showToast(result.message || 'Validation failed', 'error');
    }
  } catch (err) {
    showToast('Network or connection error.', 'error');
  }
}

function logout() {
  localStorage.removeItem('nexus_token');
  localStorage.removeItem('nexus_user');
  currentUser = null;
  init();
}

// ── Non-blocking toast notification ──────────────────────────
function showToast(message, type = 'info') {
  const toast = document.createElement('div');
  toast.textContent = message;
  toast.style.cssText = `
    position:fixed; bottom:30px; right:30px; padding:14px 22px;
    background:${type==='success'?'rgba(56,161,105,0.95)':type==='error'?'rgba(229,62,62,0.95)':'rgba(50,130,184,0.95)'};
    color:white; border-radius:8px; box-shadow:0 4px 20px rgba(0,0,0,0.4);
    font-weight:600; font-size:0.9em; z-index:9999;
    animation: fadeIn 0.3s ease;
    max-width:350px;
  `;
  document.body.appendChild(toast);
  setTimeout(() => toast.remove(), 3500);
}

// === Navigation & Roles ===
function setupNavigation() {
  const nav = document.getElementById('nav-links');
  nav.innerHTML = '';
  
  document.getElementById('welcomeMsg').textContent = currentUser.name;
  document.getElementById('roleBadge').textContent = currentUser.role.replace('_',' ').toUpperCase();

  if (currentUser.role === 'employee') {
    nav.innerHTML = `
      <button class="active" onclick="switchTab('employeeView', this)"><i class="fa-solid fa-ticket"></i> <span>My Tickets</span></button>
      <button onclick="switchTab('inventoryView', this)"><i class="fa-solid fa-boxes-stacked"></i> <span>Inventory</span></button>
      <button onclick="switchTab('requestView', this)"><i class="fa-solid fa-file-signature"></i> <span>Requests</span></button>
    `;
    switchTab('employeeView', nav.children[0]);
  } else if (currentUser.role === 'inventory_manager') {
    nav.innerHTML = `
      <button class="active" onclick="switchTab('managerView', this)"><i class="fa-solid fa-warehouse"></i> <span>Warehouse Hub</span></button>
      <button onclick="switchTab('inventoryView', this)"><i class="fa-solid fa-list-check"></i> <span>Manage Stock</span></button>
    `;
    switchTab('managerView', nav.children[0]);
  } else if (currentUser.role === 'support_agent') {
    nav.innerHTML = `
      <button class="active" onclick="switchTab('supportAgentView', this)"><i class="fa-solid fa-headset"></i> <span>Support Desk</span></button>
    `;
    switchTab('supportAgentView', nav.children[0]);
  } else if (currentUser.role === 'it_technician') {
    nav.innerHTML = `
      <button class="active" onclick="switchTab('technicianView', this)"><i class="fa-solid fa-wrench"></i> <span>Tech Portal</span></button>
    `;
    switchTab('technicianView', nav.children[0]);
  } else if (currentUser.role === 'system_admin') {
    nav.innerHTML = `
      <button class="active" onclick="switchTab('systemAdminView', this)"><i class="fa-solid fa-user-shield"></i> <span>System Control</span></button>
    `;
    switchTab('systemAdminView', nav.children[0]);
  }
}

function switchTab(viewId, btn) {
  // Hide all app views (sections inside main)
  document.querySelectorAll('.content-area .section').forEach(s => s.style.display = 'none');
  const target = document.getElementById(viewId);
  if (target) {
    target.style.display = 'block';
    target.classList.add('active');
  }

  // Active state for buttons
  document.querySelectorAll('#nav-links button').forEach(b => b.classList.remove('active'));
  if (btn) btn.classList.add('active');

  // Load specific dash data
  if (viewId === 'employeeView') loadEmployeeDash();
  if (viewId === 'managerView') loadManagerDash();
  if (viewId === 'supportAgentView') loadSupportDash();
  if (viewId === 'technicianView') loadTechnicianDash();
  if (viewId === 'systemAdminView') loadSystemAdminDash();
  if (viewId === 'inventoryView') loadInventory();
  if (viewId === 'requestView') loadUserRequests();
}

// Re-using showView logic but keeping unique names for clarity if needed
// function showAppView(viewId) { document.querySelectorAll('.section').forEach(s => s.classList.remove('active')); document.getElementById(viewId).classList.add('active'); }

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

    let ticketsHtml = '<tr><td colspan="5">No active tickets.</td></tr>';
    if (ticketsRes && ticketsRes.success && ticketsRes.tickets.length) {
      ticketsHtml = ticketsRes.tickets.map(t => `
        <tr>
          <td>#${t.id}</td>
          <td><strong>${t.title}</strong></td>
          <td><span class="badge ${t.priority==='High'?'badge-high':(t.priority==='Medium'?'badge-medium':'badge-low')}">${t.priority}</span></td>
          <td><span class="badge badge-${t.status.toLowerCase().replace(' ','-')}">${t.status}</span></td>
          <td>
            <div style="font-size:0.9em;">${t.tech_name || '<i style="color:var(--text-muted)">Unassigned</i>'}</div>
            <button class="action-btn bg-blue mt-10" onclick="openProgressModal(${t.id}, '${t.title.replace(/'/g,"\\'")}', '${t.status}')">📊 Progress</button>
          </td>
        </tr>
      `).join('');
    }

    let requestsHtml = '<tr><td colspan="4">No item requests.</td></tr>';
    if (requestsRes && requestsRes.success && requestsRes.requests.length) {
      requestsHtml = requestsRes.requests.map(r => `
        <tr>
          <td>#${r.id}</td>
          <td>${r.item_name}</td>
          <td>${r.quantity}</td>
          <td><span class="badge badge-${r.status.toLowerCase()}">${r.status}</span></td>
        </tr>
      `).join('');
    }

    const itemsOptions = (itemsRes && itemsRes.success) 
      ? itemsRes.inventory.map(i => `<option value="${i.id}">${i.name} (Available: ${i.stock})</option>`).join('')
      : '<option value="">No items available</option>';

    const empView = document.getElementById('employeeView');
    if (empView) {
      empView.innerHTML = `
        <div class="dashboard-grid">
          <div class="glass-card card-stat">
            <div class="stat-icon"><i class="fa-solid fa-ticket"></i></div>
            <div>
              <p class="desc">Active Tickets</p>
              <h3>${ticketsRes.tickets?.length || 0}</h3>
            </div>
          </div>
          <div class="glass-card card-stat">
            <div class="stat-icon"><i class="fa-solid fa-hourglass-half"></i></div>
            <div>
              <p class="desc">Pending Requests</p>
              <h3>${requestsRes.requests?.filter(r=>r.status==='Pending').length || 0}</h3>
            </div>
          </div>
        </div>

        <div class="glass-card full-width mb-20">
          <h3><i class="fa-solid fa-plus-circle"></i> New Support Ticket</h3>
          <div class="add-user-grid mt-10">
            <input id="empTicketTitle" class="premium-input" placeholder="What's the issue? (e.g. Printer Jam)">
            <select id="empTicketPriority" class="premium-input">
              <option value="Low">Low Priority</option>
              <option value="Medium">Medium</option>
              <option value="High">High</option>
            </select>
          </div>
          <textarea id="empTicketDesc" class="premium-input" placeholder="Provide more details for the technician..."></textarea>
          <button class="btn btn-primary" onclick="submitTicket()">Submit Ticket <i class="fa-solid fa-paper-plane"></i></button>
        </div>

        <div class="dashboard-grid">
          <div class="glass-card">
            <h3><i class="fa-solid fa-clock-rotate-left"></i> My Recent Tickets</h3>
            <div class="table-glass mt-10">
              <table>
                <thead><tr><th>ID</th><th>Title</th><th>Priority</th><th>Status</th><th>Technician</th></tr></thead>
                <tbody id="empTicketsTable">${ticketsHtml}</tbody>
              </table>
            </div>
          </div>
          
          <div class="glass-card">
            <h3><i class="fa-solid fa-cart-plus"></i> Request Inventory</h3>
            <div style="display:flex;gap:10px;margin:15px 0;">
              <select id="empItemSelect" class="premium-input" style="flex-grow:1;margin-bottom:0;">
                ${itemsOptions}
              </select>
              <input type="number" id="empItemQty" value="1" min="1" class="premium-input" style="width:80px;margin-bottom:0;">
            </div>
            <button class="btn btn-outline full-width" onclick="requestItem()">Submit Request</button>
            <hr style="margin:20px 0; border:0; border-top:1px solid var(--glass-border);">
            <h3>My Asset Requests</h3>
            <div class="table-glass mt-10">
              <table>
                <thead><tr><th>ID</th><th>Item</th><th>Qty</th><th>Status</th></tr></thead>
                <tbody id="empRequestsTable">${requestsHtml}</tbody>
              </table>
            </div>
          </div>
        </div>
      `;
    }
  } catch (err) { console.error('Employee dash error:', err); }
}

async function submitTicket() {
  const title = document.getElementById('empTicketTitle').value;
  const desc = document.getElementById('empTicketDesc').value;
  const pri = document.getElementById('empTicketPriority').value;
  if (!title) return alert('Issue Title is mandatory.');

  const res = await fetchAuth('/tickets', {
    method: 'POST',
    body: JSON.stringify({ title, description: desc, priority: pri })
  });
  if (res.success) {
    document.getElementById('empTicketTitle').value = '';
    document.getElementById('empTicketDesc').value = '';
    await loadEmployeeDash();
  } else alert(res.message);
}

async function requestItem() {
  const item_id = document.getElementById('empItemSelect').value;
  const qty = document.getElementById('empItemQty').value;
  if (!item_id) return alert('Select an asset first.');

  const res = await fetchAuth('/requests', {
    method: 'POST',
    body: JSON.stringify({ item_id, quantity: qty })
  });
  if (res.success) {
    document.getElementById('empItemQty').value = '1';
    await loadEmployeeDash();
  } else alert(res.message);
}

// ==============================
// SUPPORT AGENT LOGIC
// ==============================
async function loadSupportDash() {
  try {
    const [ticketsRes, techRes] = await Promise.all([
      fetchAuth('/tickets'),
      fetchAuth('/technicians')
    ]);

    const technicians = techRes.success ? techRes.technicians : [];
    const supportView = document.getElementById('supportAgentView');
    if (!supportView) return;

    const pendingCount = ticketsRes.tickets?.filter(t => t.status === 'Open').length || 0;
    const inProgressCount = ticketsRes.tickets?.filter(t => t.status === 'In Progress').length || 0;

    let ticketsHtml = '<tr><td colspan="7" style="text-align:center">No tickets available.</td></tr>';
    if (ticketsRes.success && ticketsRes.tickets.length) {
      ticketsHtml = ticketsRes.tickets.map(t => {
        const techOptions = `<option value="">-- Choose Technician --</option>` +
          technicians.map(tech => `<option value="${tech.id}" ${t.technician_id == tech.id ? 'selected' : ''}>${tech.name} (${tech.specialty || 'General'})</option>`).join('');
        
        const isClosed = ['Resolved', 'Refused', 'Closed'].includes(t.status);
        
        return `
          <tr>
            <td>#${t.id}</td>
            <td>${t.requester_name || '<i>Unknown</i>'}</td>
            <td><strong>${t.title}</strong></td>
            <td><span class="badge ${t.priority==='High'?'badge-high':(t.priority==='Medium'?'badge-medium':'badge-low')}">${t.priority}</span></td>
            <td><span class="badge badge-${t.status.toLowerCase().replace(' ','-')}">${t.status}</span></td>
            <td>${t.tech_name ? `<span class="badge badge-in-progress"><i class="fa-solid fa-wrench"></i> ${t.tech_name}</span>` : '<i class="desc">Unassigned</i>'}</td>
            <td>
              <div style="display:flex; gap:6px; flex-wrap:wrap;">
                ${!isClosed ? `
                  <select id="techSel_${t.id}" class="premium-input" style="width:140px; padding:6px; margin:0; font-size:0.8rem;">${techOptions}</select>
                  <button class="btn btn-primary btn-sm" style="padding:4px 10px;" onclick="assignTech(${t.id})">Assign</button>
                  <button class="btn btn-outline btn-sm" onclick="actOnTicket(${t.id}, 'resolve')">Resolve</button>
                ` : ''}
                <button class="btn btn-outline btn-sm" onclick="openProgressModal(${t.id}, '${t.title.replace(/'/g,"\\'")}', '${t.status}')"><i class="fa-solid fa-list-ul"></i> Log</button>
              </div>
            </td>
          </tr>
        `;
      }).join('');
    }

    supportView.innerHTML = `
      <div class="dashboard-grid">
        <div class="glass-card card-stat">
          <div class="stat-icon"><i class="fa-solid fa-envelope-open-text"></i></div>
          <div><p class="desc">Open Tickets</p><h3>${pendingCount}</h3></div>
        </div>
        <div class="glass-card card-stat">
          <div class="stat-icon"><i class="fa-solid fa-spinner fa-spin-pulse"></i></div>
          <div><p class="desc">In Progress</p><h3>${inProgressCount}</h3></div>
        </div>
      </div>

      <div class="glass-card">
        <h3><i class="fa-solid fa-tower-broadcast"></i> Live Ticket Stream</h3>
        <div class="table-glass mt-20">
          <table>
            <thead><tr><th>ID</th><th>User</th><th>Subject</th><th>Priority</th><th>Status</th><th>Tech</th><th>Actions</th></tr></thead>
            <tbody>${ticketsHtml}</tbody>
          </table>
        </div>
      </div>
    `;
  } catch (err) { console.error('Support dash error:', err); }
}

async function actOnTicket(id, action) {
  const res = await fetchAuth(`/tickets/${id}/${action}`, { method: 'POST' });
  if (res && res.success) {
    showToast(`✅ Ticket ${action === 'resolve' ? 'Resolved' : 'Refused'}`, 'success');
    if (currentUser.role === 'support_agent') loadSupportDash();
    if (currentUser.role === 'it_technician') loadTechnicianDash();
  } else showToast('❌ ' + (res?.message || 'Action failed'), 'error');
}

async function assignTech(ticketId) {
  const tech_id = document.getElementById(`techSel_${ticketId}`).value;
  if (!tech_id) return showToast('Please select a technician first.', 'error');

  const res = await fetchAuth(`/tickets/${ticketId}/assign`, {
    method: 'POST',
    body: JSON.stringify({ tech_id: Number(tech_id) })
  });
  if (res && res.success) {
    showToast('✅ Technician assigned!', 'success');
    loadSupportDash();
  } else showToast('❌ ' + (res?.message || 'Assignment failed'), 'error');
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

    const managerView = document.getElementById('managerView');
    if (!managerView) return;

    const lowStockCount = invRes.inventory?.filter(i => i.stock <= i.threshold).length || 0;
    const pendingRequests = reqRes.requests?.filter(r => r.status === 'Pending') || [];

    managerView.innerHTML = `
      <div class="dashboard-grid">
        <div class="glass-card card-stat">
          <div class="stat-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
          <div><p class="desc">Active Assets</p><h3>${invRes.inventory?.length || 0}</h3></div>
        </div>
        <div class="glass-card card-stat">
          <div class="stat-icon" style="color:var(--danger); background:rgba(248,113,113,0.1);"><i class="fa-solid fa-triangle-exclamation"></i></div>
          <div><p class="desc">Low Stock Alerts</p><h3>${lowStockCount}</h3></div>
        </div>
      </div>

      <div class="dashboard-grid mt-20">
        <div class="glass-card">
          <h3><i class="fa-solid fa-bell"></i> Critical Demands</h3>
          <div class="table-glass mt-10">
            <table>
              <thead><tr><th>Requester</th><th>Item</th><th>Qty</th><th>Actions</th></tr></thead>
              <tbody>
                ${pendingRequests.length ? pendingRequests.map(r => `
                  <tr>
                    <td>${r.employee_name}</td>
                    <td>${r.item_name}</td>
                    <td>${r.quantity}</td>
                    <td>
                      <button class="btn btn-primary btn-sm" onclick="actOnRequest(${r.id}, 'validate')">Authorize</button>
                      <button class="btn btn-danger btn-sm" onclick="actOnRequest(${r.id}, 'refuse')">Decline</button>
                    </td>
                  </tr>
                `).join('') : '<tr><td colspan="4">No pending demands.</td></tr>'}
              </tbody>
            </table>
          </div>
        </div>

        <div class="glass-card">
          <h3><i class="fa-solid fa-plus-circle"></i> Add New Asset</h3>
          <div class="add-user-grid mt-10">
            <input id="mgrItemName" class="premium-input" placeholder="Item Descriptor">
            <input type="number" id="mgrItemStock" class="premium-input" placeholder="Initial Stock">
          </div>
          <button class="btn btn-primary full-width" onclick="addNewItem()">Register Asset <i class="fa-solid fa-box-open"></i></button>
        </div>
      </div>
    `;
  } catch (err) { console.error('Manager dash error:', err); }
}

async function addNewItem() {
  const name = document.getElementById('mgrItemName').value;
  const stock = document.getElementById('mgrItemStock').value;
  if (!name) return alert('Item descriptor required');

  const res = await fetchAuth('/inventory', {
    method: 'POST',
    body: JSON.stringify({ name, stock, entering: stock, threshold: 5, quality: 'Good' })
  });
  if (res.success) {
    document.getElementById('mgrItemName').value = '';
    document.getElementById('mgrItemStock').value = '';
    await loadManagerDash();
  } else alert(res.message);
}

async function actOnRequest(id, action) {
  const res = await fetchAuth(`/requests/${id}/${action}`, { method: 'POST' });
  if (res.success) await loadManagerDash(); else alert(res.message);
}

// ==============================
// SYSTEM ADMIN LOGIC
// ==============================
function adminTab(tabId, btn) {
  document.querySelectorAll('.admin-tab-content').forEach(el => el.classList.remove('active'));
  document.querySelectorAll('.admin-tab').forEach(el => el.classList.remove('active'));
  document.getElementById(tabId).classList.add('active');
  if (btn) btn.classList.add('active');

  if (tabId === 'adminUsersTab')  loadAdminUsers();
  if (tabId === 'adminPrivTab')   loadAdminPrivileges();
  if (tabId === 'adminAuditTab')  loadAdminAudit();
  if (tabId === 'adminTechTab')   loadAdminTechnicians();
  if (tabId === 'adminConfigTab') loadAdminConfig();
}

async function loadSystemAdminDash() {
  const adminView = document.getElementById('systemAdminView');
  if (!adminView) return;

  adminView.innerHTML = `
    <div class="glass-card mb-20" style="padding:10px;">
      <div style="display:flex; gap:10px;">
        <button class="btn btn-outline btn-sm admin-tab active" onclick="adminTab('adminUsersTab', this)"><i class="fa-solid fa-users"></i> Users</button>
        <button class="btn btn-outline btn-sm admin-tab" onclick="adminTab('adminTechTab', this)"><i class="fa-solid fa-wrench"></i> Staff</button>
        <button class="btn btn-outline btn-sm admin-tab" onclick="adminTab('adminPrivTab', this)"><i class="fa-solid fa-key"></i> Privileges</button>
        <button class="btn btn-outline btn-sm admin-tab" onclick="adminTab('adminAuditTab', this)"><i class="fa-solid fa-chart-line"></i> Audit</button>
        <button class="btn btn-outline btn-sm admin-tab" onclick="adminTab('adminConfigTab', this)"><i class="fa-solid fa-gears"></i> Config</button>
      </div>
    </div>

    <div id="adminUsersTab" class="admin-tab-content active">
      <div class="glass-card mb-20">
        <h3><i class="fa-solid fa-user-plus"></i> Provision New User</h3>
        <div class="add-user-grid mt-10">
          <input id="newUserName" class="premium-input" placeholder="Full Name">
          <input id="newUserEmail" class="premium-input" placeholder="Email">
          <input id="newUserPass" type="password" class="premium-input" placeholder="Password (default: password123)">
          <select id="newUserRole" class="premium-input">
            <!-- Populated dynamically -->
          </select>
        </div>
        <button class="btn btn-primary" onclick="createUser()">Create Account <i class="fa-solid fa-user-check"></i></button>
      </div>
      <div class="glass-card">
        <h3>System User Directory</h3>
        <div class="table-glass mt-10">
          <table>
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Privileges</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody id="adminUsersTable"></tbody>
          </table>
        </div>
      </div>
    </div>

    <div id="adminTechTab" class="admin-tab-content">
       <div class="glass-card mb-20">
        <h3><i class="fa-solid fa-user-gear"></i> Add IT Technician</h3>
        <div class="add-user-grid mt-10">
          <input id="newTechName" class="premium-input" placeholder="Name">
          <input id="newTechEmail" class="premium-input" placeholder="Email">
          <input id="newTechPass" type="password" class="premium-input" placeholder="Password">
          <input id="newTechSpec" class="premium-input" placeholder="Specialty (e.g. Network)">
          <input id="newTechPhone" class="premium-input" placeholder="Phone">
        </div>
        <button class="btn btn-primary" onclick="createTechnicianUser()">Register Tech <i class="fa-solid fa-microchip"></i></button>
      </div>
      <div class="glass-card">
        <h3>Technical Staff Roster</h3>
        <div class="table-glass mt-10">
          <table>
            <thead><tr><th>Name</th><th>Specialty</th><th>Phone</th><th>Email</th><th>Actions</th></tr></thead>
            <tbody id="adminTechTable"></tbody>
          </table>
        </div>
      </div>
    </div>

    <div id="adminPrivTab" class="admin-tab-content">
      <div class="dashboard-grid">
        <div class="glass-card">
          <h3><i class="fa-solid fa-book"></i> Privilege Dictionary</h3>
          <p class="desc">Available administrative rights in Nexus v4.0:</p>
          <ul style="list-style:none; padding:0;" class="mt-10">
            <li class="mb-10">
              <strong style="color:var(--primary);">can_add_user</strong><br>
              <span class="desc" style="font-size:0.8rem;">Allows non-admins to provision new accounts and manage staff rosters.</span>
            </li>
            <li class="desc" style="font-size:0.8rem; border-top:1px solid var(--glass-border); padding-top:10px;">
              <i>More privileges coming in v4.1...</i>
            </li>
          </ul>
        </div>
        
        <div class="glass-card">
          <h3><i class="fa-solid fa-users-gear"></i> Delegation Rights</h3>
          <p class="desc">Assign specific administrative powers to trusted users.</p>
          <div class="table-glass">
            <table>
              <thead><tr><th>User</th><th>Core Role</th><th>Active Rights</th><th>Assign New</th></tr></thead>
              <tbody id="adminPrivTable"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div id="adminAuditTab" class="admin-tab-content">
      <div class="dashboard-grid">
        <div class="glass-card">
          <h3><i class="fa-solid fa-chart-pie"></i> Role Distribution</h3>
          <ul id="auditRoleList" class="mt-20" style="list-style:none;"></ul>
        </div>
        <div class="glass-card">
          <h3><i class="fa-solid fa-fingerprint"></i> Delegation Audit</h3>
          <ul id="auditPrivList" class="mt-20" style="list-style:none;"></ul>
        </div>
      </div>
      <div class="glass-card mt-20 text-center">
        <p class="desc">Total Nexus Accounts managed under Eneo:</p>
        <h2 id="auditTotal" style="font-size:3rem; color:var(--primary);">0</h2>
      </div>
    </div>

    <div id="adminConfigTab" class="admin-tab-content">
      <div class="dashboard-grid">
        <div class="glass-card">
          <h3><i class="fa-solid fa-plus-circle"></i> Create New Role</h3>
          <p class="desc mb-10">Define a new organizational role for your system.</p>
          <div style="display:flex; flex-direction:column; gap:10px;">
            <input id="newRoleName" class="premium-input" placeholder="Role Name (e.g. Finance)">
            <input id="newRoleDesc" class="premium-input" placeholder="Short Description">
            <button class="btn btn-primary" onclick="createRole()">Create Role</button>
          </div>
          <div class="mt-20">
            <h4>Existing Directory Roles</h4>
            <div id="currentRolesList" class="mt-10" style="display:flex; flex-wrap:wrap; gap:8px;"></div>
          </div>
        </div>

        <div class="glass-card">
          <h3><i class="fa-solid fa-shield-plus"></i> Create New Privilege</h3>
          <p class="desc mb-10">Define a new system right for selective delegation.</p>
          <div style="display:flex; flex-direction:column; gap:10px;">
            <input id="newPrivName" class="premium-input" placeholder="Privilege (e.g. can_view_logs)">
            <input id="newPrivDesc" class="premium-input" placeholder="Short Description">
            <button class="btn btn-primary" onclick="createPrivilege()">Create Privilege</button>
          </div>
          <div class="mt-20">
            <h4>Available System Rights</h4>
            <div id="currentPrivsList" class="mt-10" style="display:flex; flex-wrap:wrap; gap:8px;"></div>
          </div>
        </div>
      </div>
    </div>
  `;

  loadAdminUsers();
  loadRolesForForms();
}

async function loadRolesForForms() {
  try {
    const res = await fetchAuth('/roles');
    const roles = res.roles || [];
    const select = document.getElementById('newUserRole');
    if (select) {
      select.innerHTML = roles.map(r => `<option value="${r.name}">${r.name.replace(/_/g,' ').toUpperCase()}</option>`).join('');
    }
  } catch(err) { console.error(err); }
}

async function loadAdminConfig() {
  try {
    const roleRes = await fetchAuth('/roles');
    const privRes = await fetchAuth('/available_privileges');
    
    document.getElementById('currentRolesList').innerHTML = (roleRes.roles||[]).map(r => `
      <div class="badge badge-low" style="display:flex; align-items:center; gap:8px;">
        ${r.name} 
        <i class="fa-solid fa-trash" style="cursor:pointer; font-size:0.7rem;" onclick="deleteRole('${r.name}')"></i>
      </div>`).join('') || 'None';

    document.getElementById('currentPrivsList').innerHTML = (privRes.privileges||[]).map(p => `
      <div class="badge badge-medium" style="display:flex; align-items:center; gap:8px;">
        ${p.name} 
        <i class="fa-solid fa-trash" style="cursor:pointer; font-size:0.7rem;" onclick="deletePrivilege('${p.name}')"></i>
      </div>`).join('') || 'None';
  } catch(err) { console.error(err); }
}

async function createRole() {
  const name = document.getElementById('newRoleName').value.trim();
  const desc = document.getElementById('newRoleDesc').value.trim();
  if(!name) return;
  const res = await fetchAuth('/roles', { method:'POST', body:JSON.stringify({name, description:desc})});
  if(res.success) {
    showToast(`Role '${name}' created`, 'success');
    loadAdminConfig();
    loadRolesForForms();
  } else showToast(res.message, 'error');
}

async function createPrivilege() {
  const name = document.getElementById('newPrivName').value.trim();
  const desc = document.getElementById('newPrivDesc').value.trim();
  if(!name) return;
  const res = await fetchAuth('/available_privileges', { method:'POST', body:JSON.stringify({name, description:desc})});
  if(res.success) {
    showToast(`Privilege '${name}' created`, 'success');
    loadAdminConfig();
    updatePrivDictionary();
  } else showToast(res.message, 'error');
}

async function deleteRole(name) {
  if(!confirm(`Delete role '${name}'?`)) return;
  const res = await fetchAuth(`/roles/${name}`, { method:'DELETE' });
  if(res.success) { showToast('Role deleted', 'success'); loadAdminConfig(); loadRolesForForms(); }
  else showToast(res.message, 'error');
}

async function deletePrivilege(name) {
  if(!confirm(`Delete privilege '${name}'?`)) return;
  const res = await fetchAuth(`/available_privileges/${name}`, { method:'DELETE' });
  if(res.success) { showToast('Privilege deleted', 'success'); loadAdminConfig(); updatePrivDictionary(); }
  else showToast(res.message, 'error');
}

async function updatePrivDictionary() {
  // Update the selects in the Privileges tab if needed
  if(document.getElementById('adminPrivTab')) loadAdminPrivileges();
}

async function loadAdminUsers() {
  try {
    const res = await fetchAuth('/users');
    if (!res || !res.success) return;

    const roles = {
      'system_admin': 'System Administrators',
      'support_agent': 'Support Agents',
      'it_technician': 'IT Technicians',
      'inventory_manager': 'Inventory Managers',
      'employee': 'Staff Employees'
    };

    // Catch-all: Find roles NOT in our standard list
    const userList = res.users || [];
    const otherRoles = [...new Set(userList.map(u => u.role || 'employee'))].filter(r => !roles[r]);
    otherRoles.forEach(r => roles[r] = `Other: ${r.toUpperCase()}`);

    let html = '';
    
    // Group users by role
    for (const [roleKey, roleTitle] of Object.entries(roles)) {
      const group = userList.filter(u => (u.role || 'employee') === roleKey);
      if (group.length === 0) continue;

      // Add Role Header Row
      html += `
        <tr class="role-group-header" style="background:rgba(0, 210, 255, 0.05);">
          <td colspan="6" style="padding:15px; border-bottom:1px solid var(--primary);">
            <div style="display:flex; align-items:center; gap:10px; color:var(--primary); font-weight:800; text-transform:uppercase; font-size:0.8rem; letter-spacing:1px;">
              <i class="fa-solid fa-folder-tree"></i> ${roleTitle} (${group.length})
            </div>
          </td>
        </tr>
      `;

      group.forEach(u => {
        const uid = Number(u.id);
        const isSelf = uid === currentUser?.id;
        const roleStr = (u.role || 'employee');
        const roleLabel = roleStr.replace(/_/g,' ').toUpperCase();
        const privs = (u.privileges || []).map(p => `<span class="badge badge-low">${p}</span>`).join(' ') || '<i class="desc">None</i>';

        html += `<tr>
          <td><strong>${u.name || 'Unknown'}</strong></td>
          <td>${u.email || 'N/A'}</td>
          <td><span class="badge ${roleStr==='system_admin'?'badge-high':'badge-low'}">${roleLabel}</span></td>
          <td>${privs}</td>
          <td>${u.created_at ? new Date(u.created_at).toLocaleDateString() : 'N/A'}</td>
          <td>
            ${isSelf ? '<i class="desc">You</i>' : `
              <div style="display:flex; gap:5px; align-items:center;">
                <select id="rs_${uid}" class="premium-input" style="width:130px; padding:4px; margin:0; font-size:0.8rem;">
                  <option value="employee" ${roleStr==='employee'?'selected':''}>Employee</option>
                  <option value="support_agent" ${roleStr==='support_agent'?'selected':''}>Support</option>
                  <option value="inventory_manager" ${roleStr==='inventory_manager'?'selected':''}>Inventory</option>
                  <option value="system_admin" ${roleStr==='system_admin'?'selected':''}>Admin</option>
                  <option value="it_technician" ${roleStr==='it_technician'?'selected':''}>Technician</option>
                </select>
                <button class="btn btn-primary btn-sm" style="padding:4px 8px;" onclick="changeRole(${uid})">Set</button>
                <button class="btn btn-danger btn-sm" style="padding:4px 8px;" onclick="removeUser(${uid}, '${(u.name||'').replace(/'/g, "\\'")}')">Del</button>
              </div>
            `}
          </td>
        </tr>`;
      });
    }

    const tableEl = document.getElementById('adminUsersTable');
    if (tableEl) {
      tableEl.innerHTML = html || '<tr><td colspan="6" style="padding:40px; text-align:center;">No users found. Diagnostic: API returned ' + userList.length + ' accounts.</td></tr>';
    }
  } catch(err) { 
    console.error(err);
    document.getElementById('adminUsersTable').innerHTML = '<tr><td colspan="6" style="padding:40px; text-align:center; color:var(--primary);">System Diagnostic: RENDER_ERROR. Please check role dictionary.</td></tr>';
  }
}

async function createUser() {
  const name  = document.getElementById('newUserName').value.trim();
  const email = document.getElementById('newUserEmail').value.trim();
  const pass  = document.getElementById('newUserPass').value.trim();
  const role  = document.getElementById('newUserRole').value;
  
  if (!name || !email) return showToast('Name and email are required', 'error');

  const res = await fetchAuth('/users', {
    method: 'POST',
    body: JSON.stringify({ name, email, password: pass || 'password123', role })
  });
  
  if (res && res.success) {
    document.getElementById('newUserName').value = '';
    document.getElementById('newUserEmail').value = '';
    document.getElementById('newUserPass').value = '';
    showToast(`Account created for ${name}`, 'success');
    loadAdminUsers();
  } else showToast(res?.message || 'Failed', 'error');
}

async function changeRole(userId) {
  const role = document.getElementById(`rs_${userId}`).value;
  const res = await fetchAuth(`/users/${userId}/role`, {
    method: 'POST',
    body: JSON.stringify({ role })
  });
  if (res.success) {
    showToast('Role updated', 'success');
    loadAdminUsers();
  } else showToast(res.message, 'error');
}

async function removeUser(userId, name) {
  if (!confirm(`Delete ${name}?`)) return;
  const res = await fetchAuth(`/users/${userId}`, { method: 'DELETE' });
  if (res.success) {
    showToast('User removed', 'success');
    loadAdminUsers();
  } else showToast(res.message, 'error');
}

async function loadAdminPrivileges() {
  try {
    const res = await fetchAuth('/users');
    const pRes = await fetchAuth('/available_privileges');
    const availPrivs = pRes.privileges || [];

    const others = res.users.filter(u => u.id !== currentUser.id && u.role !== 'system_admin');
    
    document.getElementById('adminPrivTable').innerHTML = others.map(u => {
      const activeRights = (u.privileges || []).map(p => `
        <div class="badge badge-low" style="display:inline-flex; align-items:center; gap:5px; margin-bottom:4px;">
          ${p} <i class="fa-solid fa-circle-xmark" style="cursor:pointer; opacity:0.7;" onclick="revokePriv(${u.id}, '${p}')"></i>
        </div>
      `).join(' ') || '<i class="desc">None</i>';

      const privOptions = availPrivs.map(p => `<option value="${p.name}">${p.name}</option>`).join('');

      return `
        <tr>
          <td><strong>${u.name}</strong></td>
          <td><span class="badge">${u.role.replace(/_/g,' ')}</span></td>
          <td style="max-width:200px;">${activeRights}</td>
          <td>
            <div style="display:flex; gap:5px;">
              <select id="pSel_${u.id}" class="premium-input" style="padding:4px 8px; font-size:0.85rem; margin:0;">
                ${privOptions}
              </select>
              <button class="btn btn-primary btn-sm" style="padding:4px 10px;" onclick="grantPriv(${u.id})">
                <i class="fa-solid fa-plus"></i>
              </button>
            </div>
          </td>
        </tr>
      `;
    }).join('') || '<tr><td colspan="4">No eligible users.</td></tr>';
  } catch(err) { console.error(err); }
}

async function grantPriv(userId) {
  const priv = document.getElementById(`pSel_${userId}`).value;
  const res = await fetchAuth(`/users/${userId}/privileges`, { 
    method: 'POST', 
    body: JSON.stringify({ privilege: priv }) 
  });
  if (res.success) {
    showToast(`Granted: ${priv}`, 'success');
    loadAdminPrivileges();
  } else showToast(res.message, 'error');
}

async function revokePriv(userId, priv) {
  if(!confirm(`Revoke '${priv}' from this user?`)) return;
  const res = await fetchAuth(`/users/${userId}/privileges/${priv}`, { method: 'DELETE' });
  if (res.success) {
    showToast(`Revoked: ${priv}`, 'success');
    loadAdminPrivileges();
  } else showToast(res.message, 'error');
}

async function loadAdminAudit() {
  try {
    const res = await fetchAuth('/users/audit');
    const roleIcons = { employee:'👨‍💼', inventory_manager:'📦', support_agent:'🛠️', system_admin:'⚙️', it_technician:'🔧' };
    document.getElementById('auditRoleList').innerHTML = Object.entries(res.by_role).map(([role, count]) =>
      `<li class="mb-10"><span>${roleIcons[role]||''} ${role.replace(/_/g,' ').toUpperCase()}</span>: <strong class="text-gradient">${count}</strong></li>`
    ).join('');
    document.getElementById('auditPrivList').innerHTML = res.privileged_users.map(u =>
      `<li class="mb-10"><strong>${u.name}</strong>: <span class="badge badge-low">${u.privileges.join(', ')}</span></li>`
    ).join('') || '<li>No elevated privileges.</li>';
    document.getElementById('auditTotal').textContent = res.total;
  } catch(err) { console.error(err); }
}

async function loadAdminTechnicians() {
  try {
    const res = await fetchAuth('/users');
    const technicians = res.users.filter(u => u.role === 'it_technician');
    document.getElementById('adminTechTable').innerHTML = technicians.map(t => `
      <tr>
        <td><strong>${t.name}</strong></td>
        <td>${t.specialty || 'General'}</td>
        <td>${t.phone || 'N/A'}</td>
        <td>${t.email}</td>
        <td><button class="btn btn-danger btn-sm" onclick="removeUser(${t.id}, '${t.name}')">Remove</button></td>
      </tr>
    `).join('') || '<tr><td colspan="5">No technicians.</td></tr>';
  } catch(err) { console.error(err); }
}

async function createTechnicianUser() {
  const name  = document.getElementById('newTechName').value.trim();
  const email = document.getElementById('newTechEmail').value.trim();
  const pass  = document.getElementById('newTechPass').value.trim();
  const spec  = document.getElementById('newTechSpec').value.trim();
  const phone = document.getElementById('newTechPhone').value.trim();

  if (!name || !email || !pass) return showToast('Name, Email and Password required', 'error');

  const res = await fetchAuth('/users', {
    method: 'POST',
    body: JSON.stringify({ name, email, password: pass, role: 'it_technician', specialty: spec, phone: phone })
  });
  if (res.success) {
    showToast('Technician registered', 'success');
    loadAdminTechnicians();
  } else showToast(res.message, 'error');
}

async function loadTechnicianDash() {
  try {
    const res = await fetchAuth('/tickets');
    const techView = document.getElementById('technicianView');
    if (!techView) return;

    const myTickets = res.tickets || [];
    const active = myTickets.filter(t => !['Resolved', 'Refused', 'Closed'].includes(t.status)).length;

    techView.innerHTML = `
      <div class="dashboard-grid">
        <div class="glass-card card-stat">
          <div class="stat-icon"><i class="fa-solid fa-wrench"></i></div>
          <div><p class="desc">Assigned</p><h3>${myTickets.length}</h3></div>
        </div>
        <div class="glass-card card-stat">
          <div class="stat-icon"><i class="fa-solid fa-bolt"></i></div>
          <div><p class="desc">Active</p><h3>${active}</h3></div>
        </div>
      </div>
      <div class="glass-card mt-20">
        <h3>Task Roster</h3>
        <div class="table-glass mt-10">
          <table>
            <thead><tr><th>ID</th><th>User</th><th>Subject</th><th>Priority</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
              ${myTickets.map(t => `
                <tr>
                  <td>#${t.id}</td>
                  <td>${t.requester_name}</td>
                  <td><strong>${t.title}</strong></td>
                  <td><span class="badge ${t.priority==='High'?'badge-high':(t.priority==='Medium'?'badge-medium':'badge-low')}">${t.priority}</span></td>
                  <td><span class="badge badge-${t.status.toLowerCase().replace(' ','-')}">${t.status}</span></td>
                  <td>
                    <button class="btn btn-primary btn-sm" onclick="openProgressModal(${t.id}, '${t.title.replace(/'/g,"\\'")}', '${t.status}')">Update</button>
                    ${!['Resolved', 'Refused', 'Closed'].includes(t.status) ? `<button class="btn btn-outline btn-sm" onclick="actOnTicket(${t.id}, 'resolve')">Resolve</button>` : ''}
                  </td>
                </tr>
              `).join('') || '<tr><td colspan="6">No tasks.</td></tr>'}
            </tbody>
          </table>
        </div>
      </div>
    `;
  } catch (err) { console.error(err); }
}

async function loadInventory() {
  try {
    const res = await fetchAuth('/inventory');
    const invView = document.getElementById('inventoryView');
    if (!invView) return;

    invView.innerHTML = `
      <div class="glass-card">
        <h3><i class="fa-solid fa-warehouse"></i> Enterprise Asset Catalog</h3>
        <div class="table-glass mt-20">
          <table>
            <thead><tr><th>Item</th><th>Stock</th><th>Quality</th><th>Status</th></tr></thead>
            <tbody>
              ${res.inventory ? res.inventory.map(i => `
                <tr>
                  <td><strong>${i.name}</strong></td>
                  <td style="font-size:1.1rem; font-weight:700;">${i.stock}</td>
                  <td>${i.quality}</td>
                  <td><span class="badge ${i.stock <= i.threshold ? 'badge-high' : 'badge-low'}">${i.stock <= i.threshold ? 'Low Stock' : 'Available'}</span></td>
                </tr>
              `).join('') : '<tr><td colspan="4">No inventory data.</td></tr>'}
            </tbody>
          </table>
        </div>
      </div>
    `;
  } catch(err) { console.error(err); }
}

async function loadUserRequests() {
  try {
    const res = await fetchAuth('/requests');
    const reqView = document.getElementById('requestView');
    if (!reqView) return;

    reqView.innerHTML = `
      <div class="glass-card">
        <h3><i class="fa-solid fa-receipt"></i> Asset Request Ledger</h3>
        <div class="table-glass mt-20">
          <table>
            <thead><tr><th>ID</th><th>Asset</th><th>Qty</th><th>Status</th></tr></thead>
            <tbody>
              ${res.requests ? res.requests.map(r => `
                <tr>
                  <td>#${r.id}</td>
                  <td><strong>${r.item_name}</strong></td>
                  <td>${r.quantity}</td>
                  <td><span class="badge badge-${r.status.toLowerCase()}">${r.status}</span></td>
                </tr>
              `).join('') : '<tr><td colspan="4">No requests.</td></tr>'}
            </tbody>
          </table>
        </div>
      </div>
    `;
  } catch(err) { console.error(err); }
}

// ==============================
// SHARED PROGRESS MODAL LOGIC
// ==============================
let activeTicketId = null;

async function openProgressModal(ticketId, title, status) {
  activeTicketId = ticketId;
  document.getElementById('modalTicketTitle').textContent = `Progress: ${title} (#${ticketId})`;
  document.getElementById('progressModal').style.display = 'flex';
  document.getElementById('progressLog').innerHTML = '<p class="text-center">Loading updates...</p>';
  document.getElementById('progressText').value = '';
  document.getElementById('progressStatus').value = '';
  
  const isTerminal = ['Resolved', 'Refused', 'Closed'].includes(status);
  document.getElementById('progressInputArea').style.display = isTerminal ? 'none' : 'block';

  const saveBtn = document.getElementById('sendProgressBtn');
  saveBtn.onclick = () => submitProgressUpdate(ticketId);

  await loadProgressUpdates(ticketId);
}

function closeProgressModal() {
  document.getElementById('progressModal').style.display = 'none';
  activeTicketId = null;
}

async function loadProgressUpdates(ticketId) {
  try {
    const res = await fetchAuth(`/tickets/${ticketId}/updates`);
    if (res && res.success) {
      document.getElementById('progressLog').innerHTML = res.updates.map(u => `
        <div class="update-item">
          <div class="update-meta">
            <span><strong>${u.user_name}</strong> <span class="update-role">${u.user_role.replace('_',' ').toUpperCase()}</span></span>
            <span>${new Date(u.created_at).toLocaleString()}</span>
          </div>
          <div class="update-text">${u.update_text}</div>
        </div>
      `).join('') || '<p class="text-center desc">No progress notes yet.</p>';
      const log = document.getElementById('progressLog');
      log.scrollTop = log.scrollHeight;
    }
  } catch(err) { console.error(err); }
}

async function submitProgressUpdate(ticketId) {
  const text = document.getElementById('progressText').value.trim();
  const status = document.getElementById('progressStatus').value;

  if (!text && !status) return showToast('Note or status required.', 'error');

  try {
    if (text) {
      await fetchAuth(`/tickets/${ticketId}/updates`, { method: 'POST', body: JSON.stringify({ update_text: text }) });
    }
    if (status) {
      await fetchAuth(`/tickets/${ticketId}/status`, { method: 'POST', body: JSON.stringify({ status }) });
    }

    showToast('Progress updated', 'success');
    document.getElementById('progressText').value = '';
    document.getElementById('progressStatus').value = '';
    await loadProgressUpdates(ticketId);
    
    if (currentUser.role === 'employee') loadEmployeeDash();
    if (currentUser.role === 'support_agent') loadSupportDash();
    if (currentUser.role === 'it_technician') loadTechnicianDash();
  } catch(err) { showToast('Error updating progress', 'error'); }
}

// === Scroll Reveal System ===
function initScrollReveal() {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('reveal-active');
      }
    });
  }, { threshold: 0.1 });

  // Select elements to reveal
  const targets = document.querySelectorAll('.features-grid > div, .stats-section > div, .hero-section');
  targets.forEach(t => {
    t.classList.add('reveal-item');
    observer.observe(t);
  });
}

// Boot up
init();
initScrollReveal();

