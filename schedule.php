<?php
session_start();
require_once 'db_connect.php';

$appt_success = '';
$appt_error   = '';
$inq_success  = '';
$inq_error    = '';

/* ── Handle Appointment Submission ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_appointment'])) {
    $full_name       = trim(htmlspecialchars($_POST['full_name']       ?? ''));
    $email           = trim(htmlspecialchars($_POST['email']           ?? ''));
    $contact_number  = trim(htmlspecialchars($_POST['contact_number']  ?? ''));
    $applicant_level = trim(htmlspecialchars($_POST['applicant_level'] ?? ''));
    $preferred_date  = trim($_POST['preferred_date']  ?? '');
    $preferred_time  = trim($_POST['preferred_time']  ?? '');
    $purpose         = trim(htmlspecialchars($_POST['purpose']         ?? ''));
    $notes           = trim(htmlspecialchars($_POST['notes']           ?? ''));

    if (!$full_name || !$email || !$contact_number || !$applicant_level || !$preferred_date || !$preferred_time || !$purpose) {
        $appt_error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $appt_error = 'Please enter a valid email address.';
    } else {
        $stmt = $conn->prepare("INSERT INTO appointments (full_name,email,contact_number,applicant_level,preferred_date,preferred_time,purpose,notes) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssss", $full_name, $email, $contact_number, $applicant_level, $preferred_date, $preferred_time, $purpose, $notes);
        if ($stmt->execute()) {
            $appt_success = 'Your appointment has been scheduled! Our admissions team will contact you shortly to confirm.';
        } else {
            $appt_error = 'Something went wrong. Please try again.';
        }
        $stmt->close();
    }
}

/* ── Handle Inquiry Submission ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_inquiry'])) {
    $full_name = trim(htmlspecialchars($_POST['inq_full_name'] ?? ''));
    $email     = trim(htmlspecialchars($_POST['inq_email']     ?? ''));
    $subject   = trim(htmlspecialchars($_POST['inq_subject']   ?? ''));
    $message   = trim(htmlspecialchars($_POST['inq_message']   ?? ''));

    if (!$full_name || !$email || !$subject || !$message) {
        $inq_error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $inq_error = 'Please enter a valid email address.';
    } else {
        $stmt = $conn->prepare("INSERT INTO inquiries (full_name,email,subject,message) VALUES (?,?,?,?)");
        $stmt->bind_param("ssss", $full_name, $email, $subject, $message);
        if ($stmt->execute()) {
            $inq_success = "Your inquiry has been received! We'll respond within 1–2 business days.";
        } else {
            $inq_error = 'Something went wrong. Please try again.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Schedule an Appointment | Amore Academy</title>
  <link rel="stylesheet" href="1HCI.CSS"/>
  <link rel="icon" type="image/png" href="icon.png"/>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:ital,wght@0,400;0,600;0,700;1,400;1,600&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet"/>

  <style>
  /* ════════════════════════════════════════
     SCHEDULE PAGE — ELEVATED DESIGN
  ════════════════════════════════════════ */

  /* ── HERO ── */
  .s-hero {
    margin-top: var(--navbar-height);
    min-height: clamp(380px, 52vh, 560px);
    position: relative;
    display: flex;
    align-items: center;
    overflow: hidden;
  }
  .s-hero__photo {
    position: absolute; inset: 0;
    background: url('hci_bg.jpg') center 35% / cover no-repeat;
  }
  .s-hero__dim {
    position: absolute; inset: 0;
    background: linear-gradient(130deg,
      rgba(4,14,40,.96) 0%,
      rgba(8,28,80,.88) 50%,
      rgba(14,44,110,.78) 100%);
  }
  /* Fine dot grid overlay */
  .s-hero__dots {
    position: absolute; inset: 0;
    background-image: radial-gradient(rgba(255,255,255,.055) 1px, transparent 1px);
    background-size: 28px 28px;
  }
  /* Gold shimmer bottom border */
  .s-hero__gold {
    position: absolute; bottom: 0; left: 0; right: 0; height: 3px;
    background: linear-gradient(90deg,
      transparent 0%, rgba(201,162,39,.0) 5%,
      #c9a227 25%, #ffe169 50%, #c9a227 75%,
      rgba(201,162,39,.0) 95%, transparent 100%);
  }
  .s-hero__inner {
    position: relative; z-index: 3;
    width: 100%; max-width: var(--container-max);
    margin: 0 auto;
    padding: clamp(3.5rem,7vw,6rem) var(--container-padding);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 2.5rem;
  }
  /* Eyebrow */
  .s-hero__tag {
    display: inline-flex; align-items: center; gap: .45rem;
    background: rgba(201,162,39,.14);
    border: 1px solid rgba(201,162,39,.38);
    border-radius: 100px;
    padding: .3rem .9rem;
    font-size: .68rem; font-weight: 700;
    letter-spacing: 1.8px; text-transform: uppercase;
    color: #ffe169;
    margin-bottom: 1.2rem;
  }
  .s-hero__tag::before {
    content: ''; width: 5px; height: 5px; border-radius: 50%;
    background: #ffe169;
    animation: blink 2.5s ease-in-out infinite;
  }
  @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.25} }

  .s-hero__h1 {
    font-family: var(--font-display);
    font-size: clamp(2.4rem, 5.5vw, 4.1rem);
    font-weight: 700; line-height: 1.08;
    color: #fff; margin: 0 0 1.1rem;
  }
  .s-hero__h1 em { font-style: italic; color: #ffe169; }

  .s-hero__p {
    font-size: clamp(.88rem, 1.4vw, 1.05rem);
    color: rgba(255,255,255,.62);
    line-height: 1.78; max-width: 490px;
    margin: 0 0 2rem;
    font-weight: 300;
  }
  /* Quick-info pills */
  .s-hero__pills { display: flex; flex-wrap: wrap; gap: .45rem; }
  .s-hero__pill {
    display: inline-flex; align-items: center; gap: .38rem;
    background: rgba(255,255,255,.07);
    border: 1px solid rgba(255,255,255,.12);
    border-radius: 100px;
    padding: .32rem .85rem;
    font-size: .73rem; font-weight: 500;
    color: rgba(255,255,255,.75);
    backdrop-filter: blur(6px);
  }
  /* Faded watermark logo */
  .s-hero__watermark {
    flex-shrink: 0;
    width: clamp(100px,13vw,185px);
    opacity: .08;
    pointer-events: none;
  }
  .s-hero__watermark img { width: 100%; filter: brightness(10); }
  @media (max-width: 700px) {
    .s-hero__watermark { display: none; }
    .s-hero__inner { padding-block: 3rem; }
  }

  /* ── BODY SECTION ── */
  .s-body {
    background: #eaecf3;
    padding: clamp(3rem,6vw,5.5rem) 0 clamp(4rem,8vw,7rem);
    position: relative;
  }
  .s-body::before {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: linear-gradient(90deg, #0a2463, #c9a227 40%, #fb8500 60%, #0a2463);
    opacity: .35;
  }

  /* ── STEPS BANNER ── */
  .s-steps {
    background: linear-gradient(128deg, #060f2a 0%, #0a2463 100%);
    border-radius: 18px;
    padding: 1.875rem 2.25rem;
    margin-bottom: 3rem;
    display: grid;
    grid-template-columns: repeat(3,1fr);
    gap: 1.75rem;
    position: relative; overflow: hidden;
    box-shadow: 0 12px 48px rgba(4,14,40,.35), 0 2px 8px rgba(4,14,40,.2);
  }
  /* Dot texture */
  .s-steps::before {
    content: '';
    position: absolute; inset: 0;
    background-image: radial-gradient(rgba(201,162,39,.07) 1px, transparent 1px);
    background-size: 20px 20px;
    pointer-events: none;
  }
  /* Faint vertical dividers */
  .s-step:not(:first-child)::before {
    content: '';
    position: absolute; left: -0.875rem; top: 15%; bottom: 15%;
    width: 1px;
    background: rgba(255,255,255,.08);
  }
  .s-step {
    position: relative; z-index: 1;
    display: flex; align-items: flex-start; gap: .9rem;
  }
  .s-step__n {
    width: 38px; height: 38px; border-radius: 50%;
    background: rgba(201,162,39,.16);
    border: 1.5px solid rgba(201,162,39,.42);
    color: #ffe169;
    font-size: .82rem; font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    font-family: var(--font-body);
  }
  .s-step__title {
    font-size: .88rem; font-weight: 700;
    color: #fff; margin: 0 0 .22rem;
    font-family: var(--font-body);
  }
  .s-step__desc { font-size: .74rem; color: rgba(255,255,255,.46); margin: 0; line-height: 1.6; }
  @media (max-width: 640px) {
    .s-steps { grid-template-columns: 1fr; padding: 1.5rem; }
    .s-step:not(:first-child)::before { display: none; }
  }

  /* ── SECTION HEADING ── */
  .s-head {
    text-align: center;
    margin-bottom: clamp(2rem,4vw,3.25rem);
  }
  .s-head__label {
    display: inline-block;
    background: rgba(10,36,99,.08);
    border: 1px solid rgba(10,36,99,.16);
    border-radius: 100px;
    padding: .28rem .9rem;
    font-size: .67rem; font-weight: 700;
    letter-spacing: 1.3px; text-transform: uppercase;
    color: #0a2463;
    margin-bottom: .8rem;
  }
  .s-head h2 {
    font-family: var(--font-display);
    font-size: clamp(1.75rem, 3.5vw, 2.6rem);
    font-weight: 700; color: #060f2a;
    margin: 0 0 .45rem;
  }
  .s-head p { font-size: .88rem; color: #6b7c9a; margin: 0; }

  /* ── FORM GRID ── */
  .s-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: clamp(1.25rem, 2.5vw, 2rem);
    align-items: start;
  }
  @media (max-width: 920px) { .s-grid { grid-template-columns: 1fr; } }

  /* ── FORM CARD ── */
  .s-card {
    background: #fff;
    border-radius: 22px;
    overflow: hidden;
    border: 1px solid rgba(10,36,99,.07);
    box-shadow:
      0 1px 3px rgba(0,0,0,.04),
      0 10px 40px rgba(10,36,99,.09);
    transition: transform .28s cubic-bezier(.34,1.56,.64,1), box-shadow .28s ease;
  }
  .s-card:hover {
    transform: translateY(-4px);
    box-shadow:
      0 2px 8px rgba(0,0,0,.05),
      0 20px 56px rgba(10,36,99,.14);
  }

  /* Card header */
  .s-card__hd {
    padding: 1.75rem 2rem;
    display: flex; align-items: center; gap: 1rem;
    position: relative; overflow: hidden;
  }
  .s-card__hd--navy {
    background: linear-gradient(128deg, #060f2a 0%, #0a2463 58%, #1a3a8f 100%);
  }
  .s-card__hd--amber {
    background: linear-gradient(128deg, #6b3400 0%, #bf6f00 58%, #f08000 100%);
  }
  /* Decorative ring */
  .s-card__hd::after {
    content: '';
    position: absolute; right: -22px; top: -22px;
    width: 110px; height: 110px; border-radius: 50%;
    background: rgba(255,255,255,.055);
    border: 1px solid rgba(255,255,255,.07);
    pointer-events: none;
  }
  .s-card__ico {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: rgba(255,255,255,.13);
    border: 1px solid rgba(255,255,255,.2);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0,0,0,.12);
  }
  .s-card__title {
    font-family: var(--font-display);
    font-size: clamp(1.1rem, 2vw, 1.35rem);
    font-weight: 700; color: #fff;
    margin: 0 0 .18rem; line-height: 1.18;
  }
  .s-card__sub { font-size: .77rem; color: rgba(255,255,255,.58); margin: 0; }

  .s-card__body { padding: 2rem; }
  @media (max-width: 480px) {
    .s-card__hd   { padding: 1.3rem 1.4rem; }
    .s-card__body { padding: 1.3rem 1.4rem; }
  }

  /* ── ALERTS ── */
  .s-alert {
    display: flex; align-items: flex-start; gap: .75rem;
    padding: 1rem 1.2rem;
    border-radius: 12px;
    font-size: .86rem; font-weight: 500;
    margin-bottom: 1.5rem;
    animation: alertIn .3s ease;
  }
  @keyframes alertIn { from{opacity:0;transform:translateY(-7px)} to{opacity:1;transform:translateY(0)} }
  .s-alert--ok  { background: #edfaf4; border: 1px solid #86efac; color: #14532d; }
  .s-alert--err { background: #fef2f2; border: 1px solid #fca5a5; color: #7f1d1d; }
  .s-alert__ico { font-size: 1.1rem; flex-shrink: 0; margin-top: .05rem; }
  .s-alert strong { display: block; margin-bottom: .1rem; }

  /* ── FORM LAYOUT ── */
  .s-fg { display: grid; grid-template-columns: 1fr 1fr; gap: .9rem 1.1rem; }
  .s-fg .w2 { grid-column: 1/-1; }
  @media (max-width: 520px) {
    .s-fg { grid-template-columns: 1fr; }
    .s-fg .w2 { grid-column: auto; }
  }
  .s-f { display: flex; flex-direction: column; gap: .4rem; }

  /* Label */
  .s-lbl {
    font-size: .67rem; font-weight: 700;
    letter-spacing: .75px; text-transform: uppercase;
    color: #7a8aaa;
  }
  .s-lbl .r  { color: #d97706; margin-left: 2px; }
  .s-lbl .op { font-weight: 400; text-transform: none; letter-spacing: 0; font-size: .69rem; color: #b0bacf; }

  /* Icon-prefixed input wrapper */
  .s-iw { position: relative; }
  .s-iw__i {
    position: absolute; left: .9rem; top: 50%; transform: translateY(-50%);
    font-size: .85rem; color: #aab4cc;
    pointer-events: none;
    transition: color .18s;
  }
  .s-iw:focus-within .s-iw__i { color: #0a2463; }

  .s-iw input,
  .s-iw select {
    width: 100%;
    padding: .73rem 1rem .73rem 2.55rem;
    border: 1.5px solid #dde3ef;
    border-radius: 10px;
    font-size: .875rem; font-family: var(--font-body);
    color: #16213a;
    background: #f6f8fc;
    outline: none;
    appearance: none; -webkit-appearance: none;
    transition: border-color .18s, box-shadow .18s, background .18s;
  }
  .s-iw input::placeholder { color: #becad9; }
  .s-iw input:focus,
  .s-iw select:focus {
    border-color: #0a2463;
    box-shadow: 0 0 0 3.5px rgba(10,36,99,.1);
    background: #fff;
  }
  .s-iw select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8'%3E%3Cpath d='M1 1.5l5 5 5-5' stroke='%2394a3b8' stroke-width='1.6' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right .9rem center;
    background-color: #f6f8fc;
    padding-right: 2.4rem;
    cursor: pointer;
  }
  .s-iw select:focus { background-color: #fff; }

  /* Textarea (no icon) */
  .s-ta {
    width: 100%;
    padding: .75rem 1rem;
    border: 1.5px solid #dde3ef;
    border-radius: 10px;
    font-size: .875rem; font-family: var(--font-body);
    color: #16213a;
    background: #f6f8fc;
    outline: none;
    resize: vertical; min-height: 108px;
    line-height: 1.7;
    transition: border-color .18s, box-shadow .18s, background .18s;
  }
  .s-ta::placeholder { color: #becad9; }
  .s-ta:focus {
    border-color: #0a2463;
    box-shadow: 0 0 0 3.5px rgba(10,36,99,.1);
    background: #fff;
  }

  /* ── SEPARATOR ── */
  .s-rule {
    display: flex; align-items: center; gap: .625rem;
    margin: 1.4rem 0;
    font-size: .67rem; font-weight: 700;
    letter-spacing: .6px; text-transform: uppercase;
    color: #b0bacf;
  }
  .s-rule::before, .s-rule::after {
    content: ''; flex: 1; height: 1px; background: #e2e8f2;
  }

  /* ── INFO NOTE ── */
  .s-note {
    background: rgba(201,162,39,.07);
    border: 1px solid rgba(201,162,39,.22);
    border-left: 3px solid #c9a227;
    border-radius: 10px;
    padding: .82rem 1rem;
    font-size: .79rem; color: #5a6a7e;
    margin-bottom: 1.2rem; line-height: 1.65;
  }
  .s-note strong { color: #2d3a52; }

  /* ── SUBMIT BUTTONS ── */
  .s-btn {
    width: 100%; margin-top: 1.4rem;
    padding: .9rem 1.5rem;
    border: none; border-radius: 12px;
    font-size: .93rem; font-weight: 700;
    font-family: var(--font-body); letter-spacing: .15px;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: .55rem;
    position: relative; overflow: hidden;
    transition: transform .22s cubic-bezier(.34,1.56,.64,1), box-shadow .22s ease;
  }
  .s-btn::after {
    content: '';
    position: absolute; inset: 0;
    background: linear-gradient(135deg, rgba(255,255,255,.16), transparent);
    opacity: 0; transition: opacity .2s;
  }
  .s-btn:hover::after { opacity: 1; }

  .s-btn--navy {
    background: linear-gradient(128deg, #060f2a 0%, #0a2463 60%, #1a3a8f 100%);
    color: #fff;
    box-shadow: 0 4px 18px rgba(10,36,99,.3);
  }
  .s-btn--navy:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(10,36,99,.42);
  }
  .s-btn--amber {
    background: linear-gradient(128deg, #6b3400 0%, #bf6f00 60%, #f08000 100%);
    color: #fff;
    box-shadow: 0 4px 18px rgba(191,111,0,.32);
  }
  .s-btn--amber:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px rgba(240,128,0,.45);
  }
  .s-btn__arrow {
    width: 24px; height: 24px; border-radius: 50%;
    background: rgba(255,255,255,.18);
    display: inline-flex; align-items: center; justify-content: center;
    font-size: .82rem;
    margin-left: auto;
    transition: transform .2s;
  }
  .s-btn:hover .s-btn__arrow { transform: translateX(3px); }

  /* ── CONTACT GRID ── */
  .s-contacts {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .6rem;
  }
  .s-ctile {
    background: #f6f8fc;
    border: 1px solid #dde3ef;
    border-radius: 11px;
    padding: .85rem .95rem;
    display: flex; align-items: flex-start; gap: .6rem;
    transition: border-color .18s, background .18s, box-shadow .18s;
  }
  .s-ctile:hover {
    border-color: rgba(10,36,99,.2);
    background: #fff;
    box-shadow: 0 2px 12px rgba(10,36,99,.07);
  }
  .s-ctile__ico { font-size: .95rem; flex-shrink: 0; margin-top: .06rem; }
  .s-ctile__label {
    font-size: .64rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .5px;
    color: #0a2463; margin-bottom: .14rem;
  }
  .s-ctile__val { font-size: .8rem; color: #4a5a73; line-height: 1.42; }
  @media (max-width: 440px) { .s-contacts { grid-template-columns: 1fr; } }

  </style>
</head>
<body>

<!-- ════════════════════════════════════════ NAVBAR ════ -->
<nav class="navbar">
  <div class="nav-container">
    <div class="nav-brand">
      <div class="school-crest">
        <a href="1HCI.HTML"><img src="Amore_Academy_Logo.png" width="100px" alt="Amore Academy"/></a>
      </div>
      <div class="brand-text-block">
        <span class="brand-name">AMORE ACADEMY</span>
        <span class="brand-tagline">Shaping Minds, Building the Future</span>
      </div>
    </div>
    <div class="nav-toggle" id="navToggle"><span></span><span></span><span></span></div>
    <ul class="nav-menu" id="navMenu">
      <li class="nav-item"><a href="1HCI.HTML" class="nav-link">Home</a></li>
      <li class="nav-item dropdown">
        <a href="about.html" class="nav-link">About <span class="dropdown-arrow">▾</span></a>
        <div class="dropdown-menu">
          <div class="dropdown-section">
            <h4>General Information</h4>
            <a href="about.html#history">School History</a>
            <a href="about.html#mission-vision">Mission and Vision</a>
            <a href="about.html#core-values">Core Values</a>
          </div>
        </div>
      </li>
      <li class="nav-item dropdown">
        <a href="academics.html" class="nav-link">Academics <span class="dropdown-arrow">▾</span></a>
        <div class="dropdown-menu">
          <div class="dropdown-section">
            <h4>Programs Offered</h4>
            <a href="academics.html#junior-high">Junior High School</a>
            <a href="academics.html#senior-high">Senior High School</a>
          </div>
        </div>
      </li>
      <li class="nav-item dropdown">
        <a href="news.html" class="nav-link">News <span class="dropdown-arrow">▾</span></a>
        <div class="dropdown-menu">
          <a href="news.html#announcements">Announcements</a>
          <a href="news.html#events">Upcoming Events</a>
        </div>
      </li>
      <li class="nav-item dropdown">
        <a href="admissions.html" class="nav-link">Admissions <span class="dropdown-arrow">▾</span></a>
        <div class="dropdown-menu">
          <div class="dropdown-section">
            <h4>Apply &amp; Enroll</h4>
            <a href="admissions.html#apply">Apply Now</a>
            <a href="admissions.html#enroll">Enroll Online</a>
            <a href="admissions.html#procedure">Procedure for Enrollment</a>
          </div>
          <div class="dropdown-section">
            <h4>Admission Requirements</h4>
            <a href="admissions.html#requirements-jhs">Junior High School</a>
            <a href="admissions.html#requirements-shs">Senior High School</a>
          </div>
        </div>
      </li>
      <li class="nav-item"><a href="schedule.php" class="nav-link active">Schedule</a></li>
    </ul>
  </div>
</nav>

<!-- ════════════════════════════════════════ HERO ════ -->
<section class="s-hero">
  <div class="s-hero__photo"></div>
  <div class="s-hero__dim"></div>
  <div class="s-hero__dots"></div>
  <div class="s-hero__gold"></div>
  <div class="s-hero__inner">
    <div>
      <div class="s-hero__tag">Enrollment &amp; Support</div>
      <h1 class="s-hero__h1">
        Schedule an Appointment<br>
        &amp; <em>Send an Inquiry</em>
      </h1>
      <p class="s-hero__p">
        Book a consultation, campus tour, or enrollment visit — or send us your questions.
        Our admissions team will get back to you promptly.
      </p>
      <div class="s-hero__pills">
        <span class="s-hero__pill">📅&nbsp; Mon – Fri, 8AM – 5PM</span>
        <span class="s-hero__pill">📞&nbsp; (02) 8123-4567</span>
        <span class="s-hero__pill">⚡&nbsp; 1–2 Day Response</span>
      </div>
    </div>
    <div class="s-hero__watermark"><img src="Amore_Academy_Logo.png" alt=""/></div>
  </div>
</section>

<!-- ════════════════════════════════════════ BODY ════ -->
<section class="s-body">
  <div class="container">

    <!-- How It Works -->
    <div class="s-steps">
      <div class="s-step">
        <div class="s-step__n">1</div>
        <div>
          <p class="s-step__title">Fill the Form</p>
          <p class="s-step__desc">Complete the appointment or inquiry form with your correct details.</p>
        </div>
      </div>
      <div class="s-step">
        <div class="s-step__n">2</div>
        <div>
          <p class="s-step__title">We Review</p>
          <p class="s-step__desc">Our admissions team reviews your submission within 1–2 business days.</p>
        </div>
      </div>
      <div class="s-step">
        <div class="s-step__n">3</div>
        <div>
          <p class="s-step__title">Confirmation</p>
          <p class="s-step__desc">Receive a confirmation via email with your final appointment details.</p>
        </div>
      </div>
    </div>

    <!-- Section heading -->
    <div class="s-head">
      <span class="s-head__label">Get in Touch</span>
      <h2>How can we help you today?</h2>
      <p>Use the forms below to book a visit or send us your questions.</p>
    </div>

    <!-- Two-column form grid -->
    <div class="s-grid">

      <!-- ══ APPOINTMENT FORM ══ -->
      <div class="s-card">
        <div class="s-card__hd s-card__hd--navy">
          <div class="s-card__ico">📅</div>
          <div>
            <p class="s-card__title">Schedule an Appointment</p>
            <p class="s-card__sub">Book a visit, tour, or consultation</p>
          </div>
        </div>
        <div class="s-card__body">

          <?php if ($appt_success): ?>
            <div class="s-alert s-alert--ok">
              <span class="s-alert__ico">✅</span>
              <div><strong>Appointment Scheduled!</strong><?= $appt_success ?></div>
            </div>
          <?php elseif ($appt_error): ?>
            <div class="s-alert s-alert--err">
              <span class="s-alert__ico">⚠️</span>
              <div><strong>Heads up.</strong> <?= $appt_error ?></div>
            </div>
          <?php endif; ?>

          <form method="POST" action="schedule.php" novalidate>
            <div class="s-fg">

              <div class="s-f w2">
                <label class="s-lbl">Full Name <span class="r">*</span></label>
                <div class="s-iw">
                  <span class="s-iw__i">👤</span>
                  <input type="text" name="full_name" placeholder="e.g. Juan dela Cruz"
                    value="<?= (!$appt_success && isset($_POST['full_name'])) ? htmlspecialchars($_POST['full_name']) : '' ?>" required>
                </div>
              </div>

              <div class="s-f">
                <label class="s-lbl">Email Address <span class="r">*</span></label>
                <div class="s-iw">
                  <span class="s-iw__i">✉</span>
                  <input type="email" name="email" placeholder="you@email.com"
                    value="<?= (!$appt_success && isset($_POST['email'])) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                </div>
              </div>

              <div class="s-f">
                <label class="s-lbl">Contact Number <span class="r">*</span></label>
                <div class="s-iw">
                  <span class="s-iw__i">📞</span>
                  <input type="tel" name="contact_number" placeholder="09XX-XXX-XXXX"
                    value="<?= (!$appt_success && isset($_POST['contact_number'])) ? htmlspecialchars($_POST['contact_number']) : '' ?>" required>
                </div>
              </div>

              <div class="s-f w2">
                <label class="s-lbl">Applicant Level <span class="r">*</span></label>
                <div class="s-iw">
                  <span class="s-iw__i">🎓</span>
                  <select name="applicant_level" required>
                    <option value="">— Select Grade Level —</option>
                    <?php
                    $levels = ['Grade 7 (Junior High)','Grade 8 (Junior High)','Grade 9 (Junior High)',
                               'Grade 10 (Junior High)','Grade 11 (Senior High)','Grade 12 (Senior High)'];
                    $selLevel = (!$appt_success) ? ($_POST['applicant_level'] ?? '') : '';
                    foreach ($levels as $l) echo "<option value=\"$l\"".($selLevel===$l?' selected':'').">$l</option>";
                    ?>
                  </select>
                </div>
              </div>

              <div class="s-f">
                <label class="s-lbl">Preferred Date <span class="r">*</span></label>
                <div class="s-iw">
                  <span class="s-iw__i">📆</span>
                  <input type="date" name="preferred_date" min="<?= date('Y-m-d') ?>"
                    value="<?= (!$appt_success && isset($_POST['preferred_date'])) ? htmlspecialchars($_POST['preferred_date']) : '' ?>" required>
                </div>
              </div>

              <div class="s-f">
                <label class="s-lbl">Preferred Time <span class="r">*</span></label>
                <div class="s-iw">
                  <span class="s-iw__i">🕐</span>
                  <input type="time" name="preferred_time"
                    value="<?= (!$appt_success && isset($_POST['preferred_time'])) ? htmlspecialchars($_POST['preferred_time']) : '' ?>" required>
                </div>
              </div>

              <div class="s-f w2">
                <label class="s-lbl">Purpose of Appointment <span class="r">*</span></label>
                <div class="s-iw">
                  <span class="s-iw__i">📋</span>
                  <select name="purpose" required>
                    <option value="">— Select Purpose —</option>
                    <?php
                    $purposes = ['Enrollment Inquiry','Campus Tour','Scholarship Application',
                                 'Document Submission','Academic Consultation','Transfer Inquiry','Other'];
                    $selPurpose = (!$appt_success) ? ($_POST['purpose'] ?? '') : '';
                    foreach ($purposes as $p) echo "<option value=\"$p\"".($selPurpose===$p?' selected':'').">$p</option>";
                    ?>
                  </select>
                </div>
              </div>

              <div class="s-f w2">
                <label class="s-lbl">Additional Notes <span class="op">(optional)</span></label>
                <textarea class="s-ta" name="notes"
                  placeholder="Any special requests or additional information…"><?= (!$appt_success && isset($_POST['notes'])) ? htmlspecialchars($_POST['notes']) : '' ?></textarea>
              </div>

            </div>

            <button type="submit" name="submit_appointment" class="s-btn s-btn--navy">
              📅&nbsp; Schedule My Appointment
              <span class="s-btn__arrow">→</span>
            </button>
          </form>

        </div>
      </div><!-- /appointment card -->

      <!-- ══ INQUIRY FORM ══ -->
      <div class="s-card">
        <div class="s-card__hd s-card__hd--amber">
          <div class="s-card__ico">✉️</div>
          <div>
            <p class="s-card__title">Send an Inquiry</p>
            <p class="s-card__sub">Questions? We're happy to help</p>
          </div>
        </div>
        <div class="s-card__body">

          <?php if ($inq_success): ?>
            <div class="s-alert s-alert--ok">
              <span class="s-alert__ico">✅</span>
              <div><strong>Inquiry Received!</strong> <?= $inq_success ?></div>
            </div>
          <?php elseif ($inq_error): ?>
            <div class="s-alert s-alert--err">
              <span class="s-alert__ico">⚠️</span>
              <div><strong>Heads up.</strong> <?= $inq_error ?></div>
            </div>
          <?php endif; ?>

          <form method="POST" action="schedule.php" novalidate>
            <div class="s-fg">

              <div class="s-f w2">
                <label class="s-lbl">Full Name <span class="r">*</span></label>
                <div class="s-iw">
                  <span class="s-iw__i">👤</span>
                  <input type="text" name="inq_full_name" placeholder="e.g. Maria Santos"
                    value="<?= (!$inq_success && isset($_POST['inq_full_name'])) ? htmlspecialchars($_POST['inq_full_name']) : '' ?>" required>
                </div>
              </div>

              <div class="s-f w2">
                <label class="s-lbl">Email Address <span class="r">*</span></label>
                <div class="s-iw">
                  <span class="s-iw__i">✉</span>
                  <input type="email" name="inq_email" placeholder="you@email.com"
                    value="<?= (!$inq_success && isset($_POST['inq_email'])) ? htmlspecialchars($_POST['inq_email']) : '' ?>" required>
                </div>
              </div>

              <div class="s-f w2">
                <label class="s-lbl">Subject <span class="r">*</span></label>
                <div class="s-iw">
                  <span class="s-iw__i">📌</span>
                  <input type="text" name="inq_subject" placeholder="e.g. Admission requirements for Grade 7"
                    value="<?= (!$inq_success && isset($_POST['inq_subject'])) ? htmlspecialchars($_POST['inq_subject']) : '' ?>" required>
                </div>
              </div>

              <div class="s-f w2">
                <label class="s-lbl">Message <span class="r">*</span></label>
                <textarea class="s-ta" style="min-height:150px;" name="inq_message"
                  placeholder="Type your question or inquiry here…" required><?= (!$inq_success && isset($_POST['inq_message'])) ? htmlspecialchars($_POST['inq_message']) : '' ?></textarea>
              </div>

            </div>

            <div class="s-note">
              💡 <strong>Response time:</strong> We typically respond within <strong>1–2 business days</strong>.
              For urgent matters, please call our admissions office directly.
            </div>

            <button type="submit" name="submit_inquiry" class="s-btn s-btn--amber">
              ✉️&nbsp; Send My Inquiry
              <span class="s-btn__arrow">→</span>
            </button>
          </form>

          <div class="s-rule">Contact Us Directly</div>

          <div class="s-contacts">
            <div class="s-ctile">
              <span class="s-ctile__ico">📞</span>
              <div><div class="s-ctile__label">Phone</div><div class="s-ctile__val">(02) 8123-4567</div></div>
            </div>
            <div class="s-ctile">
              <span class="s-ctile__ico">🕐</span>
              <div><div class="s-ctile__label">Office Hours</div><div class="s-ctile__val">Mon–Fri, 8AM–5PM</div></div>
            </div>
            <div class="s-ctile">
              <span class="s-ctile__ico">📧</span>
              <div><div class="s-ctile__label">Email</div><div class="s-ctile__val">info@amoreacademy.edu</div></div>
            </div>
            <div class="s-ctile">
              <span class="s-ctile__ico">📍</span>
              <div><div class="s-ctile__label">Address</div><div class="s-ctile__val">123 Excellence Ave, Metro Manila</div></div>
            </div>
          </div>

        </div>
      </div><!-- /inquiry card -->

    </div><!-- /.s-grid -->
  </div><!-- /.container -->
</section>

<!-- CTA -->
<section class="cta-section">
  <div class="container">
    <div class="cta-content">
      <h2>Ready to Join Our Community?</h2>
      <p>Take the first step towards an exceptional education. Apply now and become part of our legacy of excellence.</p>
      <div class="cta-buttons">
        <a href="admissions.html#apply" class="btn btn-light">Apply Now</a>
        <a href="admissions.html#requirements-jhs" class="btn btn-outline">Admission Requirements</a>
      </div>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-column">
        <div class="footer-brand">
          <div class="school-crest">
            <svg width="40" height="40" viewBox="0 0 40 40" fill="none">
              <circle cx="20" cy="20" r="18" stroke="currentColor" stroke-width="2"/>
              <path d="M20 8L26 14H14L20 8Z" fill="currentColor"/>
              <rect x="18" y="14" width="4" height="12" fill="currentColor"/>
              <path d="M12 26C12 22 16 20 20 20C24 20 28 22 28 26" stroke="currentColor" stroke-width="2"/>
            </svg>
          </div>
          <h3>Amore Academy</h3>
        </div>
        <p>Shaping minds and building futures. Excellence in education, character, and service.</p>
      </div>
      <div class="footer-column">
        <h4>Quick Links</h4>
        <ul>
          <li><a href="about.html">About Us</a></li>
          <li><a href="academics.html">Academics</a></li>
          <li><a href="admissions.html">Admissions</a></li>
          <li><a href="news.html">News &amp; Events</a></li>
          <li><a href="schedule.php">Schedule / Inquiry</a></li>
        </ul>
      </div>
      <div class="footer-column">
        <h4>Programs</h4>
        <ul>
          <li><a href="academics.html#junior-high">Junior High School</a></li>
          <li><a href="academics.html#senior-high">Senior High School</a></li>
          <li><a href="admissions.html#requirements-jhs">JHS Requirements</a></li>
          <li><a href="admissions.html#requirements-shs">SHS Requirements</a></li>
        </ul>
      </div>
      <div class="footer-column">
        <h4>Contact</h4>
        <ul class="contact-info">
          <li>123 Excellence Avenue</li>
          <li>Education City, Metro Manila</li>
          <li>Phone: (02) 8123-4567</li>
          <li>Email: info@amoreacademy.edu</li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; <?= date('Y') ?> Amore Academy. All rights reserved.</p>
      <div class="footer-links">
        <a href="#">Privacy Policy</a><a href="#">Terms of Use</a><a href="#">Accessibility</a>
      </div>
    </div>
  </div>
</footer>

<script src="1HCI.JS"></script>
</body>
</html>