-- Fix for Room Page 500 Error
-- Run this in Supabase Dashboard → SQL Editor

-- Add make_up_room column to rooms table
ALTER TABLE public.rooms 
ADD COLUMN IF NOT EXISTS make_up_room boolean DEFAULT false;

-- Verify the column was added
SELECT column_name, data_type, is_nullable, column_default
FROM information_schema.columns
WHERE table_name = 'rooms' AND column_name = 'make_up_room'
ORDER BY ordinal_position;