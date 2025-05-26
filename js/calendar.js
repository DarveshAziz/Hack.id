const mentorsGrid = document.getElementById("mentorsGrid");
const searchInput = document.getElementById("searchInput");
const bookingModal = document.getElementById("bookingModal");
const closeModal = document.getElementById("closeModal");
const modalAvatar = document.getElementById("modalAvatar");
const modalName = document.getElementById("modalName");
const modalTitle = document.getElementById("modalTitle");
const modalBio = document.getElementById("modalBio");
const modalSkills = document.getElementById("modalSkills");
const calendarTitle = document.getElementById("calendarTitle");
const calendarDays = document.getElementById("calendarDays");
const timeSlots = document.getElementById("timeSlots");
const prevWeekBtn = document.getElementById("prevWeek");
const nextWeekBtn = document.getElementById("nextWeek");
const bookingForm = document.getElementById("bookingForm");
const confirmation = document.getElementById("confirmation");
const confirmationDetails = document.getElementById("confirmationDetails");
const submitBooking = document.getElementById("submitBooking");
const closeConfirmation = document.getElementById("closeConfirmation");

document.addEventListener("DOMContentLoaded", function () {
  Promise.all([
    fetch('data/mentors.json').then(res => res.json()),
    fetch('data/bookings.json').then(res => res.json())
  ]).then(([mentorsData, bookingsData]) => {
    mentors = mentorsData;
    bookings = bookingsData;
    renderMentors(mentors);
    initSearchFilter();
  });
});


// Variables
let currentMentor = null;
let currentDate = new Date();
let selectedDate = null;
let selectedTimeSlot = null;

// Render mentors
function renderMentors(mentorsToRender) {
  mentorsGrid.innerHTML = "";

  if (mentorsToRender.length === 0) {
    mentorsGrid.innerHTML =
      '<div class="no-results" style="grid-column: 1/-1; text-align: center; padding: 2rem;"><h3>No mentors found matching your search.</h3></div>';
    return;
  }

  mentorsToRender.forEach((mentor) => {
    const mentorCard = document.createElement("div");
    mentorCard.className = "mentor-card";

    let skillsHTML = "";
    mentor.skills.slice(0, 3).forEach((skill) => {
      skillsHTML += `<span class="skill-badge">${skill}</span>`;
    });

    if (mentor.skills.length > 3) {
      skillsHTML += `<span class="skill-badge">+${
        mentor.skills.length - 3
      } more</span>`;
    }

    mentorCard.innerHTML = `
                    <div class="mentor-header">
                        <img src="${mentor.avatar}" alt="${mentor.name}" class="mentor-avatar">
                        <h3 class="mentor-name">${mentor.name}</h3>
                        <p class="mentor-title">${mentor.title}</p>
                    </div>
                    <div class="mentor-body">
                        <p class="mentor-desc">${mentor.shortDescription}</p>
                        <div class="mentor-skills">
                            ${skillsHTML}
                        </div>
                    </div>
                    <div class="mentor-footer">
                        <button class="btn btn-primary book-btn" data-mentor-id="${mentor.id}">Book Now</button>
                    </div>
                `;

    mentorsGrid.appendChild(mentorCard);
  });

  // Add event listeners to booking buttons
  document.querySelectorAll(".book-btn").forEach((btn) => {
    btn.addEventListener("click", function () {
      const mentorId = parseInt(this.getAttribute("data-mentor-id"));
      openBookingModal(mentorId);
    });
  });
}

// Search filter
function initSearchFilter() {
  searchInput.addEventListener("input", function () {
    const query = this.value.toLowerCase().trim();

    if (query === "") {
      renderMentors(mentors);
      return;
    }

    const filteredMentors = mentors.filter((mentor) => {
      return (
        mentor.name.toLowerCase().includes(query) ||
        mentor.title.toLowerCase().includes(query) ||
        mentor.skills.some((skill) => skill.toLowerCase().includes(query)) ||
        mentor.shortDescription.toLowerCase().includes(query)
      );
    });

    renderMentors(filteredMentors);
  });
}

// Open booking modal
function openBookingModal(mentorId) {
  currentMentor = mentors.find((mentor) => mentor.id === mentorId);

  if (!currentMentor) return;

  // Populate modal content
  modalAvatar.src = currentMentor.avatar;
  modalAvatar.alt = currentMentor.name;
  modalName.textContent = currentMentor.name;
  modalTitle.textContent = currentMentor.title;
  modalBio.textContent = currentMentor.bio;

  // Populate skills
  modalSkills.innerHTML = "";
  currentMentor.skills.forEach((skill) => {
    const badge = document.createElement("span");
    badge.className = "skill-badge";
    badge.textContent = skill;
    modalSkills.appendChild(badge);
  });

  // Initialize calendar
  initCalendar();

  // Show modal
  bookingModal.style.display = "block";
  document.body.style.overflow = "hidden";

  // Reset form
  document.getElementById("fullName").value = "";
  document.getElementById("email").value = "";
  document.getElementById("message").value = "";

  // Show booking form, hide confirmation
  bookingForm.style.display = "block";
  confirmation.style.display = "none";
}

// Close modal
closeModal.addEventListener("click", function () {
  bookingModal.style.display = "none";
  document.body.style.overflow = "auto";
});

// Close when clicking outside modal content
window.addEventListener("click", function (event) {
  if (event.target === bookingModal) {
    bookingModal.style.display = "none";
    document.body.style.overflow = "auto";
  }
});

// Calendar functionality
let calendarInitialized = false;

function initCalendar() {
  currentDate = new Date();
  selectedDate = null;
  selectedTimeSlot = null;
  renderCalendar();

  if (!calendarInitialized) {
    prevWeekBtn.addEventListener("click", function () {
      currentDate.setDate(currentDate.getDate() - 7);
      renderCalendar();
    });

    nextWeekBtn.addEventListener("click", function () {
      currentDate.setDate(currentDate.getDate() + 7);
      renderCalendar();
    });

    calendarInitialized = true;
  }
}


function renderCalendar() {
  // Update month/year display
  const monthNames = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
  ];
  calendarTitle.textContent = `${
    monthNames[currentDate.getMonth()]
  } ${currentDate.getFullYear()}`;

  // Get the start of the week (Sunday)
  const startOfWeek = new Date(currentDate);
  startOfWeek.setDate(currentDate.getDate() - currentDate.getDay());

  // Clear previous days
  calendarDays.innerHTML = "";

  // Generate days for the week
   for (let i = 0; i < 7; i++) {
    const day = new Date(startOfWeek);
    day.setDate(startOfWeek.getDate() + i);

    const dayEl = document.createElement("div");
    dayEl.className = "day";

    // Add additional classes
    const today = new Date();
    if (day.toDateString() === today.toDateString()) {
      dayEl.classList.add("current");
    }

    // Cek apakah semua slot pada tanggal ini sudah dibooking
    let isFullyBooked = false;
    if (currentMentor) {
      const dateStr = day.toISOString().slice(0, 10);
      bookedSlots = bookings
        .filter(b =>
          String(b.mentor_id) == String(currentMentor.id) &&
          b.date === dateStr
        )
        .map(b => b.time);
      if (bookedSlots.length >= 16) { // 16 = jumlah defaultSlots
        isFullyBooked = true;
      }
    }

    // Disable past days atau hari yang sudah penuh
    if ((day < today && day.toDateString() !== today.toDateString()) || isFullyBooked) {
      dayEl.classList.add("disabled");
      if (isFullyBooked) {
        dayEl.title = "All slots booked";
      }
    } else {
      dayEl.style.cursor = "pointer";
      dayEl.addEventListener("click", function () {
        selectDate(day);
      });
    }

    if (selectedDate && day.toDateString() === selectedDate.toDateString()) {
      dayEl.classList.add("selected");
    }

    dayEl.textContent = day.getDate();
    calendarDays.appendChild(dayEl);
  }

  // Reset time slots
  timeSlots.innerHTML =
    "<p>Please select a date to view available time slots.</p>";

  // For demo purposes, automatically select the first available date
  const availableDays = document.querySelectorAll(".day:not(.disabled)");
  if (availableDays.length > 0 && !selectedDate) {
    const firstAvailableDay = availableDays[0];
    firstAvailableDay.click();
  }
}

function selectDate(date) {
  selectedDate = date;
  selectedTimeSlot = null;

  // Update UI
  document.querySelectorAll(".day").forEach((day) => {
    day.classList.remove("selected");
  });

  // Find the clicked day element and select it
  document.querySelectorAll(".day").forEach((dayEl) => {
    const dayNum = parseInt(dayEl.textContent);
    const dayDate = new Date(selectedDate);
    dayDate.setDate(dayNum);

    if (dayDate.toDateString() === date.toDateString()) {
      dayEl.classList.add("selected");
    }
  });

  // Generate time slots
  generateTimeSlots();
}

function generateTimeSlots() {
  timeSlots.innerHTML = "";

  // Fetch bookings.json setiap kali generate slot
  fetch('data/bookings.json')
    .then(res => res.json())
    .then(data => {
      bookings = data;

      const defaultSlots = [
        "10:00 AM", "10:30 AM", "11:00 AM", "11:30 AM", "12:00 PM",
        "12:30 PM", "1:00 PM", "1:30 PM", "2:00 PM", "2:30 PM",
        "3:00 PM", "3:30 PM", "4:00 PM", "4:30 PM", "5:00 PM", "5:30 PM"
      ];

      // Ambil slot yang sudah dibooking untuk mentor & tanggal terpilih
      let bookedSlots = [];
      if (currentMentor && selectedDate) {
        const dateStr = selectedDate.toISOString().slice(0, 10);
        bookedSlots = bookings
          .filter(b =>
            (b.mentor_id == currentMentor.id) && (b.date === dateStr))
          .map(b => b.time);
      }

      defaultSlots.forEach((slot) => {
        const timeSlotEl = document.createElement("div");
        timeSlotEl.className = "time-slot";
        timeSlotEl.textContent = slot;

        if (bookedSlots.includes(slot)) {
          timeSlotEl.classList.add("booked");
          timeSlotEl.setAttribute("title", "Already booked");
        } else {
          timeSlotEl.style.cursor = "pointer";
          timeSlotEl.addEventListener("click", function () {
            selectTimeSlot(slot);
          });
        }

        timeSlots.appendChild(timeSlotEl);
      });

      // Otomatis pilih slot pertama yang tersedia
      const availableSlots = document.querySelectorAll(".time-slot:not(.booked)");
      if (availableSlots.length > 0 && !selectedTimeSlot) {
        availableSlots[0].click();
      }
    });
}

function selectTimeSlot(slot) {
  selectedTimeSlot = slot;

  // Update UI
  document.querySelectorAll(".time-slot").forEach((timeSlot) => {
    timeSlot.classList.remove("selected");
  });

  document.querySelectorAll(".time-slot").forEach((timeSlot) => {
    if (timeSlot.textContent === slot) {
      timeSlot.classList.add("selected");
    }
  });
}

// Handle booking submission
submitBooking.addEventListener("click", function () {
  const fullName = document.getElementById("fullName").value.trim();
  const email = document.getElementById("email").value.trim();
  const message = document.getElementById("message").value.trim();

  if (!fullName || !email || !isValidEmail(email) || !message || !selectedDate || !selectedTimeSlot) {
    alert("Please complete all fields and select date/time.");
    return;
  }

  // Format tanggal ke yyyy-mm-dd
  const dateStr = selectedDate.toISOString().slice(0, 10);

  // Kirim ke server
  fetch('save_booking.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      mentor_id: currentMentor.id,
      date: dateStr,
      time: selectedTimeSlot,
      full_name: fullName,
      email: email,
      message: message
    })
  })
    .then(res => res.json())
    .then(result => {
      if (result.success) {
        // Tampilkan konfirmasi
        const formattedDate = selectedDate.toLocaleDateString("en-US", {
          weekday: "long",
          year: "numeric",
          month: "long",
          day: "numeric",
        });
        confirmationDetails.innerHTML = `
          <strong>Mentor:</strong> ${currentMentor.name}<br>
          <strong>Date:</strong> ${formattedDate}<br>
          <strong>Time:</strong> ${selectedTimeSlot}<br>
          <strong>Message:</strong> ${message.substring(0, 50)}${message.length > 50 ? "..." : ""}
        `;
        bookingForm.style.display = "none";
        confirmation.style.display = "block";
      } else {
        alert(result.message || "Booking failed.");
      }
    })
    .catch(() => alert("Booking failed. Please try again."));
});

// Close confirmation
closeConfirmation.addEventListener("click", function () {
  bookingModal.style.display = "none";
  document.body.style.overflow = "auto";
});

// Helper function to validate email
function isValidEmail(email) {
  const re =
    /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
  return re.test(String(email).toLowerCase());
}
