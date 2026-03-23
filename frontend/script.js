// === API Configuration ===
const API_URL = '../Ticket_inventory-system/index.php';

// === Data Storage ===
let tickets = [];
let inventory = [];

// === Initialization ===
async function init() {
  await fetchAll();
  render();
}

async function fetchAll() {
  try {
    const [ticketsRes, invRes] = await Promise.all([
      fetch(`${API_URL}/tickets`).then(r => r.json()),
      fetch(`${API_URL}/inventory`).then(r => r.json())
    ]);
    if (ticketsRes.success) tickets = ticketsRes.tickets;
    if (invRes.success) inventory = invRes.inventory;
  } catch (err) {
    console.error('Error fetching data:', err);
  }
}

// === Navigation ===
function show(id) {
  document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('nav button').forEach(b => b.classList.remove('active'));
  document.getElementById(id).classList.add('active');
  const navBtn = Array.from(document.querySelectorAll('nav button')).find(b => b.textContent.toLowerCase().includes(id));
  if (navBtn) navBtn.classList.add('active');
  render();
}

// === Render All ===
function render() {
  // Dashboard Stats
  document.getElementById('dashOpen').textContent = tickets.filter(t => t.status === 'Open').length;
  document.getElementById('dashItems').textContent = inventory.length;
  document.getElementById('dashLow').textContent = inventory.filter(i => parseInt(i.stock) <= parseInt(i.threshold)).length;
  document.getElementById('dashResolved').textContent = tickets.filter(t => t.status === 'Resolved').length;

  // Tickets Table
  document.getElementById('ticketTable').innerHTML = tickets.map(t => `
    <tr>
      <td>#${t.id.toString().padStart(4, '0')}</td>
      <td>${t.title}</td>
      <td>${t.priority}</td>
      <td><span class="badge badge-${t.status.toLowerCase()}">${t.status}</span></td>
      <td>
        ${t.status !== 'Resolved' ? `<button class="action-btn" style="background:#70AD47" onclick="resolve(${t.id})">✓</button>` : ''}
        <button class="action-btn btn-danger" onclick="delTicket(${t.id})">✕</button>
      </td>
    </tr>
  `).join('');

  // Inventory Table
  document.getElementById('invTable').innerHTML = inventory.map(i => {
    const stock = parseInt(i.stock);
    const threshold = parseInt(i.threshold);
    const status = stock === 0 ? 'Out' : stock <= threshold ? 'Low' : 'OK';
    const badge = stock === 0 ? 'badge-low' : stock <= threshold ? 'badge-low' : 'badge-resolved';
    return `
      <tr>
        <td>${i.name}</td>
        <td>${stock}</td>
        <td>${threshold}</td>
        <td><span class="badge ${badge}">${status}</span></td>
        <td>
          <button class="action-btn" style="background:#4472C4" onclick="updateStock(${i.id}, 1, ${stock})">+</button>
          <button class="action-btn" style="background:#EF4444" onclick="updateStock(${i.id}, -1, ${stock})">-</button>
          <button class="action-btn btn-danger" onclick="delItem(${i.id})">✕</button>
        </td>
      </tr>
    `;
  }).join('');
}

// === Ticket Actions ===
async function addTicket() {
  const title = document.getElementById('tTitle').value;
  if (!title) return alert('Enter title');
  
  const data = {
    title: title,
    priority: document.getElementById('tPriority').value
  };

  try {
    const res = await fetch(`${API_URL}/tickets`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    const result = await res.json();
    if (result.success) {
      document.getElementById('tTitle').value = '';
      await fetchAll();
      render();
    } else {
      alert('Error: ' + result.message);
    }
  } catch (err) {
    console.error('Save failed:', err);
  }
}

async function resolve(id) {
  try {
    const res = await fetch(`${API_URL}/tickets/${id}/resolve`, {
      method: 'POST'
    });
    const result = await res.json();
    if (result.success) {
      await fetchAll();
      render();
    }
  } catch (err) {
    console.error('Resolve failed:', err);
  }
}

async function delTicket(id) {
  if (confirm('Delete ticket?')) {
    try {
      const res = await fetch(`${API_URL}/tickets/${id}`, {
        method: 'DELETE'
      });
      const result = await res.json();
      if (result.success) {
        await fetchAll();
        render();
      }
    } catch (err) {
      console.error('Delete failed:', err);
    }
  }
}

// === Inventory Actions ===
async function addItem() {
  const name = document.getElementById('iName').value;
  if (!name) return alert('Enter item name');
  
  const data = {
    name: name,
    stock: parseInt(document.getElementById('iStock').value) || 0,
    threshold: parseInt(document.getElementById('iThreshold').value) || 5
  };

  try {
    const res = await fetch(`${API_URL}/inventory`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    const result = await res.json();
    if (result.success) {
      document.getElementById('iName').value = '';
      document.getElementById('iStock').value = '';
      await fetchAll();
      render();
    } else {
      alert('Error: ' + result.message);
    }
  } catch (err) {
    console.error('Add item failed:', err);
  }
}

async function updateStock(id, change, currentStock) {
  const newStock = Math.max(0, currentStock + change);
  try {
    const res = await fetch(`${API_URL}/inventory/${id}/stock`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ stock: newStock })
    });
    const result = await res.json();
    if (result.success) {
      await fetchAll();
      render();
    }
  } catch (err) {
    console.error('Update stock failed:', err);
  }
}

async function delItem(id) {
  if (confirm('Delete item?')) {
    try {
      const res = await fetch(`${API_URL}/inventory/${id}`, {
        method: 'DELETE'
      });
      const result = await res.json();
      if (result.success) {
        await fetchAll();
        render();
      }
    } catch (err) {
      console.error('Delete failed:', err);
    }
  }
}

// Start
init();