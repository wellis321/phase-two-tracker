(function () {
  var list        = document.getElementById('attendee-list');
  var emptyNote   = document.getElementById('attendee-empty');
  var userSelect  = document.getElementById('attendee-select');
  var addUserBtn  = document.getElementById('attendee-add-user');
  var extInput    = document.getElementById('attendee-external-name');
  var addExtBtn   = document.getElementById('attendee-add-external');
  if (!list) return;

  function updateEmptyNote() {
    if (!emptyNote) return;
    emptyNote.hidden = list.children.length > 0;
  }

  function alreadyAdded(uid) {
    if (!uid) return false;
    return Array.from(list.children).some(function (chip) { return chip.dataset.uid === String(uid); });
  }

  function addChip(uid, name) {
    name = name.trim();
    if (!name) return;
    if (uid && alreadyAdded(uid)) return;

    var chip = document.createElement('div');
    chip.className = 'attendee-chip';
    chip.dataset.uid = uid || '';
    chip.innerHTML =
      '<span class="attendee-chip-name"></span>' +
      '<div class="attendee-chip-toggle" role="group">' +
        '<button type="button" class="attendee-toggle-btn attendee-toggle-btn--active" data-status="attending">Attending</button>' +
        '<button type="button" class="attendee-toggle-btn" data-status="apologies">Apologies</button>' +
      '</div>' +
      '<button type="button" class="attendee-chip-remove" aria-label="Remove">&times;</button>' +
      '<input type="hidden" name="attendee_user_id[]" value="' + (uid || '') + '">' +
      '<input type="hidden" name="attendee_name[]">' +
      '<input type="hidden" name="attendee_status[]" value="attending">';
    chip.querySelector('.attendee-chip-name').textContent = name;
    chip.querySelector('input[name="attendee_name[]"]').value = name;
    list.appendChild(chip);
    updateEmptyNote();
  }

  if (addUserBtn) {
    addUserBtn.addEventListener('click', function () {
      var opt = userSelect.options[userSelect.selectedIndex];
      if (!opt || !opt.value) return;
      addChip(opt.value, opt.textContent);
      userSelect.value = '';
    });
  }

  if (addExtBtn) {
    addExtBtn.addEventListener('click', function () {
      addChip('', extInput.value);
      extInput.value = '';
      extInput.focus();
    });
    extInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); addExtBtn.click(); }
    });
  }

  list.addEventListener('click', function (e) {
    var toggleBtn = e.target.closest('.attendee-toggle-btn');
    if (toggleBtn) {
      var chip = toggleBtn.closest('.attendee-chip');
      chip.querySelectorAll('.attendee-toggle-btn').forEach(function (b) {
        b.classList.toggle('attendee-toggle-btn--active', b === toggleBtn);
      });
      chip.querySelector('input[name="attendee_status[]"]').value = toggleBtn.dataset.status;
      return;
    }
    var removeBtn = e.target.closest('.attendee-chip-remove');
    if (removeBtn) {
      removeBtn.closest('.attendee-chip').remove();
      updateEmptyNote();
    }
  });

  updateEmptyNote();
})();
