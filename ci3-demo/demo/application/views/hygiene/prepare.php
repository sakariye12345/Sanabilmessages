<?php $this->load->view('/inc/hygiene_header.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Hygiene Attendance — Prepare</title>
  

  <style>
    :root{
      --bg:#f8f9fa; --ink:#222; --muted:#6c757d;
      --card:#fff; --border:#dee2e6;
    }
    *{box-sizing:border-box}
    body{font-family:'Segoe UI',sans-serif;background:var(--bg);color:var(--ink)}
    .container{padding:1.25rem;max-width:900px;margin:0 auto}

    /* Top actions */
    .actions{display:flex;gap:.75rem;align-items:center;margin:0 0 1rem}
    .actions h1{font-size:1.5rem;margin:0;margin-right:auto}
    .submit-btn{padding:.65rem 1.25rem;background:#2e2e2e;color:#fff;border:0;border-radius:14px}
    .circle-btn{padding:.5rem .75rem;border-radius:10%;border:0;background:#eee}

    /* Info cards */
    .form-row{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem}
    .card{background:var(--card);border:1px solid var(--border);border-radius:0;padding:1rem}
    .card label{font-weight:700;margin-top:.5rem}
    .card input,.card select{width:100%;margin-top:.3rem;padding:.5rem;border:1px solid #ccc;border-radius:3px}

    /* Students block */
    .table-card{background:var(--card);border:1px solid var(--border);border-radius:0;padding:1rem}
    .students-header{display:grid;grid-template-columns:72px 1fr 220px;gap:.75rem;font-weight:700;margin-bottom:.5rem}
    .row-item{
      display:grid;grid-template-columns:72px 1fr 220px;gap:.75rem;
      align-items:center;padding:.4rem 0;border-bottom:1px solid #f1f1f1;
    }
    .row-item:last-child{border-bottom:0}
    .row-check{vertical-align:middle;margin-right:.3rem}
    .name-input{background:#edf0f2}

    .form-select{font-size:.95rem;padding:.5rem .75rem;border-radius:3px;border:1px solid #ccc}

    /* Responsive */
    @media (max-width: 900px){
      .container{padding:1rem}
    }
    @media (max-width: 768px){
      .form-row{grid-template-columns:1fr}
      .students-header{grid-template-columns:60px 1fr 160px}
      .row-item{grid-template-columns:60px 1fr 160px}
      .actions h1{font-size:1.25rem}
    }
    @media (max-width: 520px){
      /* Stack each student row vertically on phones */
      .students-header{display:none} /* hide big header */
      .row-item{
        grid-template-columns:1fr; gap:.35rem; padding:.6rem 0;
      }
      .col-roll{order:1; display:flex; align-items:center; gap:.5rem; font-weight:600}
      .col-name{order:2}
      .col-status{order:3}
      .col-status .label-sm{display:inline-block;font-size:.8rem;color:var(--muted);margin-bottom:.25rem}
      .actions{flex-wrap:wrap}
      .submit-btn,.circle-btn{width:100%}
    }
    /* --- Mobile layout: keep all in one row --- */
@media (max-width: 520px){
  /* show header again if you like; or keep hidden */
  .students-header{ display: grid; grid-template-columns: 52px 1fr 130px; gap:.5rem; }

  .row-item{
    /* one row, compact columns on phones */
    grid-template-columns: 52px 1fr 130px;
    gap: .5rem;
    align-items: center;
    padding: .45rem 0;
  }

  /* remove the stacking orders we added before */
  .col-roll, .col-name, .col-status{ order: initial; }

  /* make inputs more compact and single-line */
  .name-input{
    height: 34px;
    padding: .35rem .5rem;
    font-size: .95rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis; /* long names truncate neatly */
    background:#edf0f2;
  }

  .form-select.status-select{
    height: 34px;
    padding: .25rem .5rem;
    font-size: .95rem;
  }

  /* shrink the “All / No.” column a bit */
  .row-check{ width:18px; height:18px; vertical-align:middle; }
  .col-roll strong{ font-size: .95rem; }
}

/* --- Mobile: title on row 1, buttons on row 2 --- */
@media (max-width: 520px){
  .actions{
    display:flex;
    flex-wrap:wrap;          /* allow a second row */
    align-items:center;
    gap:.5rem .5rem;
    padding:.25rem .5rem;
    justify-content:flex-start;
  }

  /* Row 1: title takes full width */
  .actions h1{
    flex:0 0 100%;
    font-size:1.05rem;
    margin:0 0 .25rem 0;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
  }

  /* Row 2: compact buttons sit next to each other */
  .actions .submit-btn{
    padding:.45rem .8rem;
    font-size:.9rem;
    border-radius:10px;
    flex:0 0 auto;
  }
  .actions .circle-btn{
    padding:.35rem .55rem;
    font-size:.95rem;
    border-radius:8px;
    flex:0 0 auto;
  }
  .width{ width:130px }
}


  </style>
</head>
<body>

  <main class="container">
   <?= form_open('hygiene/store', ['id' => 'hygieneForm', 'method' => 'post']); ?>
  <input type="hidden"
         name="<?= $this->security->get_csrf_token_name(); ?>"
         value="<?= $this->security->get_csrf_hash(); ?>">

    <!-- Top Bar -->
    <div class="actions">
      <h1 style="margin-bottom: 8px;">Prepare Class Hygiene</h1>
      <button style="border-radius: 20px; width: 120px; background-color: black; color: white;padding: 2px" type="button" class="" id="submitBtn">Submit</button>
      <a style="border-radius: 20px; width: 120px; background-color: black; color: white; text-align: center; text-decoration: none; padding: 4px" href="<?= site_url('hygiene'); ?>" class="" title="Back">Back</a>
     
    </div>

    <!-- Hidden -->
    <input type="hidden" name="class_id" value="<?= (int)$class_id; ?>">
    <?= csrf_field(); ?>

    <!-- Info -->
    <section class="form-row">
      <div class="card">
        <label>School</label>
        <input type="text" value="Schools Demo" disabled />
        <label>Class</label>
        <input type="text" value="<?= html_escape($class_name); ?>" disabled />
      </div>
      <div class="card">
        <label>Date</label>
        <input type="date" name="attn_date" value="<?= html_escape($attn_date); ?>" required />
      </div>
    </section>

    <!-- Students -->
    <section class="table-card">
      <div class="students-header">
        <div>
          <input type="checkbox" id="checkAll" /> All
        </div>
        <div>Name</div>
        <div>Status</div>
      </div>

      <?php foreach ($students as $s):
        $sid  = (int)$s['id'];
        $roll = (int)$s['attendance_no'];
        $name = $s['full_name'];
        $prev = isset($existing[$sid]) ? $existing[$sid] : '';
      ?>
        <div class="row-item" data-row-id="<?= $sid; ?>">
          <!-- roll + row check -->
          <div class="col-roll">
            <input type="checkbox" class="row-check" />
            <strong><?= $roll; ?></strong>
          </div>

          <!-- name -->
          <div class="col-name">
            <input  type="text" class="form-control name-input width" value="<?= html_escape($name); ?>" readonly />
          </div>

          <!-- status -->
          <div class="col-status">
          
           <select class="form-select status-select" name="status[<?= $sid; ?>]">
 
  <option value="NL" <?= $prev==='NL'?'selected':''; ?>>Nails</option>
   <option value="NR" <?= $prev==='NR'?'selected':''; ?>>Normal</option>
  <option value="HR" <?= $prev==='HR'?'selected':''; ?>>Hair</option>
  <option value="BH" <?= $prev==='BH'?'selected':''; ?>>Both</option>
</select>
          </div>
        </div>
      <?php endforeach; ?>
    </section>

  <?= form_close(); ?>
  </main>

  <!-- Scripts -->
  <script>
  document.addEventListener('DOMContentLoaded', function () {
    const checkAll  = document.getElementById('checkAll');
    const submitBtn = document.getElementById('submitBtn');
    const clearBtn  = document.getElementById('clearBtn');

    const $$ = (sel) => Array.from(document.querySelectorAll(sel));

    function setAllChecks(flag){ $$('.row-check').forEach(cb => cb.checked = !!flag); }

    // 🔁 Now sets each select to "Normal" (value NR or label "normal")
    function setNormal(sel){
      for (let i=0;i<sel.options.length;i++){
        const o = sel.options[i];
        if ((o.value||'').toUpperCase()==='NR' || (o.text||'').trim().toLowerCase()==='normal'){
          sel.selectedIndex = i; sel.dispatchEvent(new Event('change')); return;
        }
      }
      sel.selectedIndex = 0; sel.dispatchEvent(new Event('change'));
    }
    function clearSelect(sel){ sel.selectedIndex = 0; sel.dispatchEvent(new Event('change')); }

    function setAllStatusesNormal(){ $$('select[name^="status["]').forEach(setNormal); }
    function clearAllStatuses(){ $$('select[name^="status["]').forEach(clearSelect); }

    if (checkAll){
      checkAll.addEventListener('change', function(){
        if (this.checked){ setAllChecks(true); setAllStatusesNormal(); }
        else{ setAllChecks(false); clearAllStatuses(); }
      });
    }

    if (clearBtn){
      clearBtn.addEventListener('click', function(){
        clearAllStatuses(); setAllChecks(false); if (checkAll) checkAll.checked = false;
      });
    }

    if (submitBtn){
      submitBtn.addEventListener('click', function(){
        const form = document.getElementById('hygieneForm'); if (form) form.submit();
      });
    }
  });
</script>


</body>
</html>
