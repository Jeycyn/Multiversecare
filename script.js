


const toggle = document.querySelector('.signin-toggle');
const dropdown = document.querySelector('.signin-options');

toggle.addEventListener('click', (e) => {
    e.preventDefault();
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
});

window.addEventListener('click', function(e) {
    if (!toggle.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.style.display = 'none';
    }
});


document.querySelector('.button').addEventListener('click', function () {
    document.getElementById('homeModal').style.display = 'block';
});

document.querySelector('.close-btn').addEventListener('click', function () {
    document.getElementById('homeModal').style.display = 'none';
});

window.addEventListener('click', function (e) {
    const modal = document.getElementById('homeModal');
    if (e.target === modal) {
        modal.style.display = 'none';
    }
});


  function openModal(modalId) {
    document.getElementById(modalId).style.display = "block";
  }

  function closeModal(modalId) {
    document.getElementById(modalId).style.display = "none";
  }

  // Close modal if clicked outside content
  window.onclick = function(event) {
    const modals = document.querySelectorAll(".modal");
    modals.forEach(modal => {
      if (event.target === modal) {
        modal.style.display = "none";
      }
    });
  };


