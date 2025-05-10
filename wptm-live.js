
setInterval(() => {
  fetch(wptm_ajax.ajax_url + '?action=wptm_get_memory')
    .then(res => res.text())
    .then(data => {
      const box = document.getElementById('live-memory-box');
      const value = document.getElementById('live-memory-value');
      value.textContent = data;
      box.style.backgroundColor = '#d4edda';
      setTimeout(() => {
        box.style.backgroundColor = '#f9f9f9';
      }, 400);
    });
}, 5000);
