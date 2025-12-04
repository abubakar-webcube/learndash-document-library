// src/utils/AccessControl.js
import Swal from 'sweetalert2';

const STORAGE_KEY_PREFIX = 'ldl_unlocked_';
const RATE_LIMIT_KEY = 'ldl_password_attempts';
const RATE_LIMIT_DURATION = 30000; // 30 seconds

/**
 * Check if a term is unlocked in current session
 */
export function isUnlocked(termId, taxonomy) {
  const key = `${STORAGE_KEY_PREFIX}${taxonomy}_${termId}`;
  return sessionStorage.getItem(key) === 'true';
}

/**
 * Mark term as unlocked in session
 */
export function markAsUnlocked(termId, taxonomy) {
  const key = `${STORAGE_KEY_PREFIX}${taxonomy}_${termId}`;
  sessionStorage.setItem(key, 'true');
}

/**
 * Check rate limiting for password attempts
 */
function isRateLimited() {
  const lastAttempt = sessionStorage.getItem(RATE_LIMIT_KEY);
  if (!lastAttempt) return false;
  
  const now = Date.now();
  const diff = now - parseInt(lastAttempt, 10);
  
  return diff < RATE_LIMIT_DURATION;
}

/**
 * Set rate limit timestamp
 */
function setRateLimit() {
  sessionStorage.setItem(RATE_LIMIT_KEY, Date.now().toString());
}

/**
 * Show SweetAlert2 message
 */
export function showMessage(title, text, icon = 'success') {
  return Swal.fire({
    title,
    text,
    icon,
    position: 'top-end',
    toast: true,
    timer: 5000,
    timerProgressBar: true,
    showConfirmButton: false,
    showClass: {
      popup: 'animate__animated animate__fadeInDown'
    },
    hideClass: {
      popup: 'animate__animated animate__fadeOutUp'
    }
  });
}

/**
 * Show password prompt
 */
export async function promptPassword(termId, taxonomy, termName, restBase, restNonce) {
  // Check rate limiting
  if (isRateLimited()) {
    showMessage(
      'Too Many Attempts',
      'Please wait 30 seconds before trying again.',
      'warning'
    );
    return false;
  }

  const result = await Swal.fire({
    title: `Enter Password for "${termName}"`,
    input: 'password',
    inputPlaceholder: 'Enter password',
    showCancelButton: true,
    confirmButtonText: 'Submit',
    showLoaderOnConfirm: true,
    inputAttributes: {
      autocapitalize: 'off',
      autocorrect: 'off'
    },
    preConfirm: async (password) => {
      if (!password) {
        Swal.showValidationMessage('Password is required');
        return false;
      }

      try {
        const response = await fetch(`${restBase}/verify-password`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            ...(restNonce ? { 'X-WP-Nonce': restNonce } : {})
          },
          body: JSON.stringify({
            term_id: termId,
            taxonomy: taxonomy,
            password: password
          })
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
          setRateLimit(); // Set rate limit on failed attempt
          throw new Error(data.message || 'Incorrect password. Please try again in 30 seconds.');
        }

        return data;
      } catch (error) {
        Swal.showValidationMessage(error.message);
        return false;
      }
    },
    allowOutsideClick: () => !Swal.isLoading()
  });

  if (result.isConfirmed && result.value) {
    markAsUnlocked(termId, taxonomy);
    showMessage('Success!', 'Access granted!', 'success');
    return true;
  }

  return false;
}

/**
 * Check access to a term (library/category)
 * Returns: { allowed: boolean, reason: string }
 */
export async function checkAccess(termId, taxonomy, action, restBase, restNonce) {
  // Check if already unlocked in session
  if (isUnlocked(termId, taxonomy)) {
    return { allowed: true, reason: '' };
  }

  try {
    const response = await fetch(`${restBase}/check-access`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        ...(restNonce ? { 'X-WP-Nonce': restNonce } : {})
      },
      body: JSON.stringify({
        term_id: termId,
        taxonomy: taxonomy,
        action: action
      })
    });

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || 'Access check failed');
    }

    // If access allowed
    if (data.allowed) {
      return { allowed: true, reason: '' };
    }

    // If role restriction failed
    if (data.reason === 'role') {
      showMessage(
        'Access Denied',
        'You don\'t have permission to access this content. Please contact site admin.',
        'error'
      );
      return { allowed: false, reason: 'role' };
    }

    // If password required
    if (data.needs_password) {
      const unlocked = await promptPassword(termId, taxonomy, data.term_name, restBase, restNonce);
      return { allowed: unlocked, reason: unlocked ? '' : 'password' };
    }

    return { allowed: false, reason: 'unknown' };

  } catch (error) {
    console.error('Access check error:', error);
    showMessage('Error', 'Unable to verify access. Please try again.', 'error');
    return { allowed: false, reason: 'error' };
  }
}

/**
 * Check access to a document by checking all its folder/library associations
 */
export async function checkDocumentAccess(document, restBase, restNonce) {
  // Get all folder IDs this document belongs to
  const getFolderIds = (doc) => {
    const raw = doc.folderIds ?? doc.folder_id ?? doc.folderId ?? doc.folder?.id;
    if (Array.isArray(raw)) return raw.map(v => Number(v)).filter(v => !Number.isNaN(v));
    if (typeof raw === 'string') {
      return raw.split(',').map(v => Number(v.trim())).filter(v => !Number.isNaN(v));
    }
    const num = Number(raw);
    return Number.isNaN(num) ? [] : [num];
  };

  const folderIds = getFolderIds(document);
  
  // If no folders, allow access
  if (folderIds.length === 0) {
    return { allowed: true, reason: '' };
  }

  // Check access to each folder
  for (const folderId of folderIds) {
    const result = await checkAccess(folderId, 'ldl_library', 'view', restBase, restNonce);
    if (!result.allowed) {
      return result; // Return first restriction encountered
    }
  }

  return { allowed: true, reason: '' };
}

/**
 * Check if term has any restrictions
 */
export async function hasRestrictions(termId, taxonomy, restBase, restNonce) {
  try {
    const response = await fetch(`${restBase}/check-access`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        ...(restNonce ? { 'X-WP-Nonce': restNonce } : {})
      },
      body: JSON.stringify({
        term_id: termId,
        taxonomy: taxonomy,
        action: 'check'
      })
    });

    const data = await response.json();
    return !data.allowed || data.needs_password;
  } catch (error) {
    console.error('Restriction check error:', error);
    return false;
  }
}