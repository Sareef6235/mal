<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
$db = connect_db($config['db']);
ensure_core_tables($db);
$topic = (string)($_GET['topic'] ?? '');
$sections = [
 ['id'=>'dashboard','icon'=>'bi-speedometer2','menu'=>'Dashboard','purpose_en'=>'Overview of entire ERP with key stats.','purpose_ml'=>'സിസ്റ്റത്തിന്റെ മൊത്തം സ്ഥിതിവിവരങ്ങൾ കാണിക്കുന്ന പ്രധാന പേജ്.','how_en'=>['Open dashboard from sidebar.','Review cards for students/exams/festivals/events.','Use quick links to navigate modules.'],'how_ml'=>['Sidebarയിൽ നിന്ന് Dashboard തുറക്കുക.','വിദ്യാർത്ഥികൾ/പരീക്ഷകൾ/ഫെസ്റ്റിവൽ കാർഡുകൾ പരിശോധിക്കുക.','Quick links ഉപയോഗിച്ച് മോഡ്യൂളിലേക്ക് പോകുക.'],'example_en'=>'Check total festivals before starting event day.','example_ml'=>'ഇവന്റ് ദിനത്തിന് മുമ്പ് മൊത്തം ഫെസ്റ്റിവലുകൾ പരിശോധിക്കുക.','tips_en'=>'Refresh after major updates for latest counts.','tips_ml'=>'പ്രധാന മാറ്റങ്ങൾക്ക് ശേഷം refresh ചെയ്യുക.'],
 ['id'=>'students','icon'=>'bi-people','menu'=>'Students','purpose_en'=>'Manage student records and imports.','purpose_ml'=>'വിദ്യാർത്ഥികളുടെ വിവരങ്ങൾ നിയന്ത്രിക്കാൻ.','how_en'=>['Add new student details.','Edit/Delete existing students.','Bulk import from CSV/Excel pages.'],'how_ml'=>['പുതിയ വിദ്യാർത്ഥിയെ ചേർക്കുക.','വിവരങ്ങൾ തിരുത്തുക/നീക്കം ചെയ്യുക.','CSV/Excel വഴി bulk import ചെയ്യുക.'],'example_en'=>'Import new academic year students in one go.','example_ml'=>'പുതിയ അധ്യയന വർഷത്തിലെ വിദ്യാർത്ഥികളെ ഒറ്റത്തവണ ഇറക്കുമതി ചെയ്യുക.','tips_en'=>'Keep unique register numbers.','tips_ml'=>'Register നമ്പർ unique ആക്കി സൂക്ഷിക്കുക.'],
 ['id'=>'attendance','icon'=>'bi-qr-code-scan','menu'=>'Attendance','purpose_en'=>'Track daily attendance with QR support.','purpose_ml'=>'ദൈനംദിന ഹാജർ QR വഴി രേഖപ്പെടുത്തൽ.','how_en'=>['Generate/view QR list.','Scan QR to mark attendance.','Check attendance reports.'],'how_ml'=>['QR ലിസ്റ്റ് സൃഷ്ടിക്കുക/കാണുക.','QR scan ചെയ്ത് ഹാജർ രേഖപ്പെടുത്തുക.','Attendance report കാണുക.'],'example_en'=>'Teacher scans student QR at class entry.','example_ml'=>'ക്ലാസ് പ്രവേശനത്തിൽ അധ്യാപകൻ QR scan ചെയ്യുന്നു.','tips_en'=>'Use clear lighting for scanner accuracy.','tips_ml'=>'സ്കാനിംഗിന് നല്ല ലൈറ്റിംഗ് ഉപയോഗിക്കുക.'],
 ['id'=>'fees','icon'=>'bi-cash-stack','menu'=>'Fees','purpose_en'=>'Collect and monitor fee payments.','purpose_ml'=>'ഫീസ് ശേഖരണം, പേയ്മെന്റ് നിരീക്ഷണം.','how_en'=>['Open fee collect page.','Enter amount and paid amount.','View pending and paid lists.'],'how_ml'=>['Fee collect പേജ് തുറക്കുക.','Amount/paid amount നൽകുക.','Pending/paid ലിസ്റ്റുകൾ കാണുക.'],'example_en'=>'Record monthly fee and update status.','example_ml'=>'മാസഫീസ് രേഖപ്പെടുത്തി status update ചെയ്യുക.','tips_en'=>'Use month format YYYY-MM for consistency.','tips_ml'=>'YYYY-MM month format ഉപയോഗിക്കുക.'],
 ['id'=>'exams','icon'=>'bi-journal-check','menu'=>'Exams','purpose_en'=>'Create and manage exams and dates.','purpose_ml'=>'പരീക്ഷകൾ സൃഷ്ടിക്കുകയും നിയന്ത്രിക്കുകയും ചെയ്യുക.','how_en'=>['Add exam id/name/type/date.','Maintain class-wise exam plan.','Use results pages for marks.'],'how_ml'=>['Exam id/name/type/date ചേർക്കുക.','ക്ലാസ് അടിസ്ഥാനത്തിൽ exam plan നിലനിർത്തുക.','Marks results പേജുകളിൽ നൽകുക.'],'example_en'=>'Create Midterm 2026 before mark entry.','example_ml'=>'മാർക്ക് എൻട്രിക്ക് മുമ്പ് Midterm 2026 സൃഷ്ടിക്കുക.','tips_en'=>'Use consistent naming for reports.','tips_ml'=>'റിപ്പോർട്ടിനായി ഒരേ പേരിടൽ പിന്തുടരുക.'],
 ['id'=>'results','icon'=>'bi-trophy','menu'=>'Results','purpose_en'=>'Enter marks, calculate totals, rank and reports.','purpose_ml'=>'മാർക്ക് എൻട്രി, മൊത്തം/റാങ്ക് കണക്കാക്കൽ.','how_en'=>['Import marks from CSV/Sheet.','View rank list and marksheet.','Publish result reports.'],'how_ml'=>['CSV/Sheet വഴി മാർക്ക് ഇറക്കുമതി ചെയ്യുക.','Rank list, marksheet കാണുക.','Result report പ്രസിദ്ധീകരിക്കുക.'],'example_en'=>'Generate class rank after import.','example_ml'=>'ഇറക്കുമതിക്ക് ശേഷം ക്ലാസ് റാങ്ക് ജനറേറ്റ് ചെയ്യുക.','tips_en'=>'Validate subject max/pass marks first.','tips_ml'=>'Subject max/pass marks ആദ്യം പരിശോധിക്കുക.'],
 ['id'=>'festivals','icon'=>'bi-calendar-event','menu'=>'Festivals','purpose_en'=>'Create/activate Milad Fest, Art Fest, Annual Fest.','purpose_ml'=>'Milad/Art/Annual Fest സൃഷ്ടിക്കൽ, സജീവമാക്കൽ.','how_en'=>['Create festival with dates/status.','Activate one festival at a time.','Manage events under active festival.'],'how_ml'=>['തീയതി/status നൽകി festival സൃഷ്ടിക്കുക.','ഒരു festival മാത്രം active ആക്കുക.','Active festival കീഴിൽ events കൈകാര്യം ചെയ്യുക.'],'example_en'=>'Activate Milad Fest before participant registration.','example_ml'=>'Participant registration മുമ്പ് Milad Fest active ആക്കുക.','tips_en'=>'Set correct year for filtering and reporting.','tips_ml'=>'ഫിൽറ്ററിംഗിനായി ശരിയായ വർഷം നൽകുക.'],
 ['id'=>'events','icon'=>'bi-megaphone','menu'=>'Events','purpose_en'=>'Manage festival competitions and categories.','purpose_ml'=>'ഫെസ്റ്റിവൽ മത്സരങ്ങളും കാറ്റഗറികളും നിയന്ത്രിക്കുക.','how_en'=>['Add event name and festival.','Select category (Boys/Girls/General etc.).','Set max score, date, venue.'],'how_ml'=>['Event name, festival തിരഞ്ഞെടുക്കുക.','Category (Boys/Girls/General...) നൽകുക.','Max score, date, venue നിശ്ചയിക്കുക.'],'example_en'=>'Create Speech - Girls event with max 100.','example_ml'=>'Speech - Girls event max 100 ആയി സൃഷ്ടിക്കുക.','tips_en'=>'Keep event names clear and short.','tips_ml'=>'Event പേരുകൾ വ്യക്തവും ചുരുക്കവുമാക്കുക.'],
 ['id'=>'participants','icon'=>'bi-person-lines-fill','menu'=>'Participants','purpose_en'=>'Register students/external participants for events.','purpose_ml'=>'മത്സരാർത്ഥികളെ event-ലേക്ക് രജിസ്റ്റർ ചെയ്യുക.','how_en'=>['Choose participant type and event.','Assign category and house.','Save to generate unique QR token.'],'how_ml'=>['Participant type, event തിരഞ്ഞെടുക്കുക.','Category, house നൽകുക.','Save ചെയ്താൽ unique QR token ലഭിക്കും.'],'example_en'=>'Register external student for Quiz - Open.','example_ml'=>'Quiz - Open ന് external participant രജിസ്റ്റർ ചെയ്യുക.','tips_en'=>'Avoid duplicate register numbers per event.','tips_ml'=>'ഒരേ event-ൽ duplicate register നമ്പർ ഒഴിവാക്കുക.'],
 ['id'=>'score-entry','icon'=>'bi-pencil-square','menu'=>'Score Entry','purpose_en'=>'Judges enter marks; system computes totals/ranks.','purpose_ml'=>'ജഡ്ജിമാർ മാർക്ക് നൽകുന്നു; മൊത്തം/റാങ്ക് സിസ്റ്റം കണക്കാക്കും.','how_en'=>['Assign judges per event.','Enter score per participant.','Review totals and ranking.'],'how_ml'=>['ഓരോ event-ന് judges നിശ്ചയിക്കുക.','മത്സരാർത്ഥിക്ക് score നൽകുക.','Totals, ranking പരിശോധിക്കുക.'],'example_en'=>'3 judges enter speech scores for each participant.','example_ml'=>'Speech event-ൽ 3 judges score നൽകുന്നു.','tips_en'=>'Use max score validation to avoid errors.','tips_ml'=>'പിഴവ് ഒഴിവാക്കാൻ max score validation ഉപയോഗിക്കുക.'],
 ['id'=>'scoreboard','icon'=>'bi-tv','menu'=>'Scoreboard','purpose_en'=>'Live leaderboard for participants and houses.','purpose_ml'=>'പങ്കാളികളും house-കളും ഉള്ള ലൈവ് ലീഡർബോർഡ്.','how_en'=>['Open Live Scoreboard page.','Display on projector/TV.','Auto refresh updates every few seconds.'],'how_ml'=>['Live Scoreboard പേജ് തുറക്കുക.','Projector/TVയിൽ പ്രദർശിപ്പിക്കുക.','ചില സെക്കൻഡിൽ auto refresh അപ്ഡേറ്റ് നടക്കും.'],'example_en'=>'Show final ranking live during closing ceremony.','example_ml'=>'ക്ലോസിംഗ് പരിപാടിയിൽ ഫൈനൽ റാങ്ക് live കാണിക്കുക.','tips_en'=>'Use full-screen browser mode for TV.','tips_ml'=>'TVയ്ക്ക് full-screen mode ഉപയോഗിക്കുക.'],
 ['id'=>'certificates','icon'=>'bi-award','menu'=>'Certificates','purpose_en'=>'Generate and download participation/winner certificates.','purpose_ml'=>'Participation/Winner certificate സൃഷ്ടിച്ച് ഡൗൺലോഡ് ചെയ്യുക.','how_en'=>['Choose participant and event.','Select certificate type.','Generate and print/download PDF.'],'how_ml'=>['Participant, event തിരഞ്ഞെടുക്കുക.','Certificate type തിരഞ്ഞെടുക്കുക.','PDF ആയി generate ചെയ്ത് print/download ചെയ്യുക.'],'example_en'=>'Generate Winner Certificate for 1st place speech.','example_ml'=>'Speech 1st place ന് Winner Certificate സൃഷ്ടിക്കുക.','tips_en'=>'Verify name spelling before final download.','tips_ml'=>'Download മുൻപ് പേര് ശരിയാണോ പരിശോധിക്കുക.'],
];
include __DIR__ . '/../layout/header.php';
include __DIR__ . '/../layout/sidebar.php';
?>
<div class="card card-soft p-3 mb-3">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h4 class="mb-1"><i class="bi bi-book-half me-2"></i>Bilingual User Guide (English + മലയാളം)</h4>
      <div class="text-muted small">Understand each menu with purpose, steps, examples and tips.</div>
    </div>
    <input id="helpSearch" class="form-control" style="max-width:320px" placeholder="Search help topics...">
  </div>
</div>
<div class="accordion" id="helpAccordion">
<?php foreach ($sections as $idx => $s): $open = ($topic !== '' && str_contains($topic, str_replace('-', '_', $s['id']))) || $idx===0; ?>
  <div class="accordion-item help-topic" id="<?= e($s['id']) ?>" data-help="<?= e(strtolower($s['menu'].' '.$s['purpose_en'].' '.$s['purpose_ml'])) ?>">
    <h2 class="accordion-header" id="head<?= (int)$idx ?>">
      <button class="accordion-button <?= $open ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#col<?= (int)$idx ?>" aria-expanded="<?= $open ? 'true' : 'false' ?>">
        <i class="bi <?= e($s['icon']) ?> me-2"></i> <?= e($s['menu']) ?>
      </button>
    </h2>
    <div id="col<?= (int)$idx ?>" class="accordion-collapse collapse <?= $open ? 'show' : '' ?>" data-bs-parent="#helpAccordion">
      <div class="accordion-body">
        <div class="row g-3">
          <div class="col-md-6"><div class="help-card"><h6>Purpose (English)</h6><p><?= e($s['purpose_en']) ?></p><h6>How to Use</h6><ol><?php foreach($s['how_en'] as $h): ?><li><?= e($h) ?></li><?php endforeach; ?></ol><h6>Example</h6><p><?= e($s['example_en']) ?></p><h6>Tips</h6><p><?= e($s['tips_en']) ?></p></div></div>
          <div class="col-md-6"><div class="help-card"><h6>ഉദ്ദേശ്യം (Malayalam)</h6><p><?= e($s['purpose_ml']) ?></p><h6>എങ്ങനെ ഉപയോഗിക്കാം</h6><ol><?php foreach($s['how_ml'] as $h): ?><li><?= e($h) ?></li><?php endforeach; ?></ol><h6>ഉദാഹരണം</h6><p><?= e($s['example_ml']) ?></p><h6>ടിപ്പുകൾ</h6><p><?= e($s['tips_ml']) ?></p></div></div>
        </div>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
