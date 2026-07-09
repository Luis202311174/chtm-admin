-- Run this in Supabase Dashboard -> SQL Editor
-- This will reset all encrypted emails to NULL since they were encrypted with a different key
-- and cannot be decrypted. Users will need to re-enter their emails.

-- Reset user emails
UPDATE public.users 
SET email = NULL, 
    email_hash = NULL
WHERE email IS NOT NULL;

-- Note: The email_hash is also reset because it was derived from the original email.
-- After users re-enter their emails, the email_hash will be regenerated automatically.

-- For archived bookings, the guest_email_hash is also unrecoverable
-- It will show as N/A in the archive until new bookings are archived with valid emails