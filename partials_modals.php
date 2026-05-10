<?php /** @var array $user */ ?>
<div class="modal" id="editProfileModal" role="dialog" aria-modal="true" aria-labelledby="editTitle">
    <div class="modal-card glass-panel">
        <button class="modal-close" data-close>&times;</button>
        <p class="eyebrow">Autosaved drafts</p><h2 id="editTitle">Edit Profile</h2>
        <form id="profileForm" class="form-grid" data-autosave="profileDraft">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label>Full Name<input name="full_name" required minlength="2" value="<?= e($user['full_name']) ?>"></label>
            <label>Username<input name="username" required pattern="[a-zA-Z0-9._-]{3,60}" value="<?= e($user['username']) ?>"></label>
            <label>Email<input type="email" name="email" required value="<?= e($user['email']) ?>"></label>
            <label>Phone<input name="phone" value="<?= e($user['phone']) ?>"></label>
            <label>Gender<select name="gender"><option><?= e($user['gender']) ?></option><option>Female</option><option>Male</option><option>Non-binary</option><option>Prefer not to say</option></select></label>
            <label>Date of Birth<input type="date" name="date_of_birth" value="<?= e($user['date_of_birth']) ?>"></label>
            <label class="wide">Address<input name="address" value="<?= e($user['address']) ?>"></label>
            <label class="wide">Bio<textarea name="bio" maxlength="500"><?= e($user['bio']) ?></textarea><small class="field-hint">Tell people what you are building.</small></label>
            <label>Website<input type="url" name="website" value="<?= e($user['website']) ?>"></label>
            <label>X/Twitter<input type="url" name="twitter" value="<?= e($user['twitter']) ?>"></label>
            <label>LinkedIn<input type="url" name="linkedin" value="<?= e($user['linkedin']) ?>"></label>
            <label>GitHub<input type="url" name="github" value="<?= e($user['github']) ?>"></label>
            <div class="modal-actions"><button type="button" class="ghost-btn" data-close>Cancel</button><button class="primary-btn" type="submit">Save Profile</button></div>
        </form>
    </div>
</div>

<div class="modal" id="passwordModal" role="dialog" aria-modal="true" aria-labelledby="passwordTitle">
    <div class="modal-card glass-panel compact">
        <button class="modal-close" data-close>&times;</button>
        <h2 id="passwordTitle">Change Password</h2>
        <form id="passwordForm" class="stack-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <label>Current Password<input type="password" name="current_password" required autocomplete="current-password"></label>
            <label>New Password<input type="password" name="new_password" id="newPassword" required minlength="10" autocomplete="new-password"></label>
            <div class="strength"><span></span><b>Strength</b></div>
            <label>Confirm Password<input type="password" name="confirm_password" required autocomplete="new-password"></label>
            <button class="primary-btn" type="submit">Update Password</button>
        </form>
    </div>
</div>

<div class="modal" id="uploadModal" role="dialog" aria-modal="true" aria-labelledby="uploadTitle">
    <div class="modal-card glass-panel compact">
        <button class="modal-close" data-close>&times;</button>
        <h2 id="uploadTitle">Upload Image</h2>
        <form id="uploadForm" class="stack-form" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="type" id="uploadType" value="avatar">
            <label class="dropzone" id="dropzone"><input type="file" name="image" accept="image/jpeg,image/png,image/webp" required><img id="imagePreview" alt="Preview"><span>Drag & drop or tap to choose JPG, PNG, or WebP</span></label>
            <button class="primary-btn" type="submit">Upload Image</button>
        </form>
    </div>
</div>

<div class="modal" id="verifyModal" role="dialog" aria-modal="true">
    <div class="modal-card glass-panel compact"><button class="modal-close" data-close>&times;</button><h2>Security Verification</h2><p class="muted">Enter the six-digit code from your authenticator app to confirm sensitive changes.</p><div class="otp-row"><input maxlength="1"><input maxlength="1"><input maxlength="1"><input maxlength="1"><input maxlength="1"><input maxlength="1"></div><button class="primary-btn" data-close data-toast="Verification UI confirmed">Verify Device</button></div>
</div>

<div class="modal" id="deleteModal" role="dialog" aria-modal="true">
    <div class="modal-card glass-panel compact"><button class="modal-close" data-close>&times;</button><h2>Delete Account</h2><p class="muted">This premium confirmation protects against accidental account deletion. Type DELETE to enable the action.</p><input id="deleteConfirm" placeholder="Type DELETE"><button class="danger-btn" id="deleteAccountBtn" disabled>Delete Account</button></div>
</div>

<div class="modal" id="logoutModal" role="dialog" aria-modal="true">
    <div class="modal-card glass-panel compact"><button class="modal-close" data-close>&times;</button><h2>Logout Confirmation</h2><p class="muted">End this secure session on the current device?</p><div class="modal-actions"><button class="ghost-btn" data-close>Stay</button><a class="primary-btn" href="logout.php">Logout</a></div></div>
</div>
