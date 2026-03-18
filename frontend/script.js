// === Data Storage ===
let tickets = JSON.parse(localStorage.getItem('tickets')) || [];
let inventory = JSON.parse(localStorage.getItem('inventory')) || [];

// === Navigation ===
function show(id) {
  document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('nav button').forEach(b => b.classList.remove('active'));
  document.getElementById(id).classList.add('active');
  event.target.classList.add('active');
  render();
}

// === Render All ===
function render() {
  // Dashboard Stats
  document.getElementById('dashOpen').textContent = tickets.filter(t => t.status === 'Open').length;
  document.getElementById('dashItems').textContent = inventory.length;
  document.getElementById('dashLow').textContent = inventory.filter(i => i.stock <= i.threshold).length;
  document.getElementById('dashResolved').textContent = tickets.filter(t => t.status === 'Resolved').length;

  // Tickets Table
  document.getElementById('ticketTable').innerHTML = tickets.map(t => `
    <tr>
      <td>#${t.id.toString().slice(-4)}</td>
      <td>${t.title}</td>
      <td>${t.priority}</td>
      <td><span class="badge badge-${t.status.toLowerCase()}">${t.status}</span></td>
      <td>
        <button class="action-btn" style="background:#70AD47" onclick="resolve(${t.id})">✓</button>
        <button class="action-btn btn-danger" onclick="delTicket(${t.id})">✕</button>
      </td>
    </tr>
  `).join('');

  // Inventory Table
  document.getElementById('invTable').innerHTML = inventory.map(i => {
    const status = i.stock === 0 ? 'Out' : i.stock <= i.threshold ? 'Low' : 'OK';
    const badge = i.stock === 0 ? 'badge-low' : i.stock <= i.threshold ? 'badge-low' : 'badge-resolved';
    return `
      <tr>
        <td>${i.name}</td>
        <td>${i.stock}</td>
        <td>${i.threshold}</td>
        <td><span class="badge ${badge}">${status}</span></td>
        <td>
          <button class="action-btn" style="background:#4472C4" onclick="updateStock(${i.id}, 1)">+</button>
          <button class="action-btn" style="background:#EF4444" onclick="updateStock(${i.id}, -1)">-</button>
          <button class="action-btn btn-danger" onclick="delItem(${i.id})">✕</button>
        </td>
      </tr>
    `;
  }).join('');
}

// === Ticket Actions ===
function addTicket() {
  const title = document.getElementById('tTitle').value;
  if (!title) return alert('Enter title');
  
  tickets.push({
    id: Date.now(),
    title: title,
    priority: document.getElementById('tPriority').value,
    status: 'Open'
  });
  
  localStorage.setItem('tickets', JSON.stringify(tickets));
  document.getElementById('tTitle').value = '';
  render();
}

function resolve(id) {
  const t = tickets.find(x => x.id === id);
  if (t) {
    t.status = 'Resolved';
    localStorage.setItem('tickets', JSON.stringify(tickets));
    render();
  }
}

function delTicket(id) {
  if (confirm('Delete ticket?')) {
    tickets = tickets.filter(t => t.id !== id);
    localStorage.setItem('tickets', JSON.stringify(tickets));
    render();
  }
}

// === Inventory Actions ===
function addItem() {
  const name = document.getElementById('iName').value;
  if (!name) return alert('Enter item name');
  
  inventory.push({
    id: Date.now(),
    name: name,
    stock: parseInt(document.getElementById('iStock').value) || 0,
    threshold: parseInt(document.getElementById('iThreshold').value) || 5
  });
  
  localStorage.setItem('inventory', JSON.stringify(inventory));
  document.getElementById('iName').value = '';
  document.getElementById('iStock').value = '';
  render();
}

function updateStock(id, change) {
  const i = inventory.find(x => x.id === id);
  if (i) {
    i.stock = Math.max(0, i.stock + change);
    localStorage.setItem('inventory', JSON.stringify(inventory));
    render();
  }
}

function delItem(id) {
  if (confirm('Delete item?')) {
    inventory = inventory.filter(i => i.id !== id);
    localStorage.setItem('inventory', JSON.stringify(inventory));
    render();
  }
}

// === Initialize ===
render();