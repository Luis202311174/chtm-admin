-- Add make_up_room column to rooms table
-- Run this in Supabase Dashboard → SQL Editor

ALTER TABLE public.rooms 
ADD COLUMN IF NOT EXISTS make_up_room boolean DEFAULT false;