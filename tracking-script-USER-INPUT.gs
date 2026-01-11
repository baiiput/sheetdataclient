// ========================================
// 🚀 ALTERNATIVE SOLUTION: USER IDENTIFICATION MENU
// ========================================
// Solusi untuk Google personal accounts yang tidak support automatic user tracking

/**
 * Fungsi ini otomatis jalan saat spreadsheet dibuka
 * Menambahkan custom menu untuk user identification
 */
function onOpen() {
  var ui = SpreadsheetApp.getUi();
  ui.createMenu('🔐 User Identity')
      .addItem('Set My Identity', 'showUserIdentityDialog')
      .addItem('Clear My Identity', 'clearUserIdentity')
      .addToUi();
}

/**
 * Show dialog untuk user input email mereka
 */
function showUserIdentityDialog() {
  var ui = SpreadsheetApp.getUi();
  var userProps = PropertiesService.getUserProperties();
  var currentEmail = userProps.getProperty('user_email') || '';

  var result = ui.prompt(
    '🔐 Set Your Identity',
    'Please enter your email address:\n(This will be used to track your changes)\n\nCurrent: ' + (currentEmail || 'Not set'),
    ui.ButtonSet.OK_CANCEL
  );

  if (result.getSelectedButton() == ui.Button.OK) {
    var email = result.getResponseText().trim();
    if (email && email.indexOf('@') > -1) {
      userProps.setProperty('user_email', email);
      ui.alert('✅ Success!', 'Your identity has been set to: ' + email, ui.ButtonSet.OK);
      Logger.log('✅ User identity set: ' + email);
    } else {
      ui.alert('❌ Error', 'Please enter a valid email address', ui.ButtonSet.OK);
    }
  }
}

/**
 * Clear user identity
 */
function clearUserIdentity() {
  var ui = SpreadsheetApp.getUi();
  var result = ui.alert(
    'Clear Identity',
    'Are you sure you want to clear your identity?',
    ui.ButtonSet.YES_NO
  );

  if (result == ui.Button.YES) {
    PropertiesService.getUserProperties().deleteProperty('user_email');
    ui.alert('✅ Cleared', 'Your identity has been cleared', ui.ButtonSet.OK);
  }
}

/**
 * Get user email - IMPROVED VERSION with UserProperties
 */
function getUserEmailImproved() {
  // Priority 1: Get from User Properties (user-set identity)
  try {
    var userProps = PropertiesService.getUserProperties();
    var savedEmail = userProps.getProperty('user_email');
    if (savedEmail && savedEmail !== '') {
      Logger.log('✅ Using saved user identity: ' + savedEmail);
      return savedEmail;
    }
  } catch (err) {
    Logger.log('⚠️ Cannot access User Properties: ' + err.message);
  }

  // Priority 2: Try Session methods (will return owner for personal Gmail)
  try {
    var email = Session.getActiveUser().getEmail();
    if (email && email !== '') {
      return email;
    }

    email = Session.getEffectiveUser().getEmail();
    if (email && email !== '') {
      return email;
    }
  } catch (error) {
    Logger.log('❌ Error getting user email: ' + error.message);
  }

  // Priority 3: Get owner email as last resort
  try {
    var email = SpreadsheetApp.getActiveSpreadsheet().getOwner().getEmail();
    if (email && email !== '') {
      Logger.log('⚠️ Using owner email as fallback: ' + email);
      return email + ' (owner-fallback)';
    }
  } catch (err) {
    Logger.log('❌ Cannot get owner email: ' + err.message);
  }

  return 'unknown-editor';
}

/**
 * REPLACE getUserEmail() calls in onEditTracking with getUserEmailImproved()
 */
