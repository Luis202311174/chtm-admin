-- Run this in Supabase Dashboard -> SQL Editor
-- This will backfill guest_email for existing archived bookings

-- First, ensure the guest_email column exists (run the add_guest_email migration SQL first)
-- ALTER TABLE public.archived_bookings ADD COLUMN IF NOT EXISTS guest_email TEXT;

-- Update archived bookings with email from users table
UPDATE public.archived_bookings ab
SET guest_email = u.email
FROM public.users u
WHERE ab.user_id = u.id
AND ab.guest_email IS NULL
AND u.email IS NOT NULL;

-- For archived bookings without a user, we cannot recover the email
-- The email was only stored as a hash (guest_email_hash) for privacy reasons