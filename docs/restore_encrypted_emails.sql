-- Run this in Supabase Dashboard -> SQL Editor
-- This will restore the emails with proper encryption using the current key

-- First, disable the trigger temporarily (it tries to hash the encrypted email, not plaintext)
DROP TRIGGER IF EXISTS sync_user_email_hash ON public.users;

-- Update each user's email with the encrypted value
-- The encrypted values are generated using the current APP_KEY: base64:OdVOqiRWcJWLZAPDTXfAlOywbVQTYS9v0k1B5I7jG1w=
-- The email_hash values are SHA256 hashes of the plaintext emails

UPDATE public.users SET 
    email = 'Y3QVuxEufnUQt9Vru1bsp51qbusEZ0chIeiSFP3UdA7won/3Lg9lAzU0IGW5kFGF',
    email_hash = '12fbd783584a6a6a8b2353d58325d7ea79135c3381853e7259e4d185803eea61'
WHERE id = '60cb8d87-0097-4d6c-940d-c30e404f4bcd';

UPDATE public.users SET 
    email = 'r7ESwgHs3/vmiPWu+/G6W86Mzzd9YIkxHaQ3NLRDfzfXfqE8mcjIysTN78WWzDLLDJTZW+zt',
    email_hash = '4575296db8cede6559cc42a617ef501e6269ab756d93d743ca0926ce0087ca91'
WHERE id = '23a08633-38a2-4b95-926d-ea9af49ce7f2';

UPDATE public.users SET 
    email = 'kw/RjIqymzM8EkcBay2mC5bX7i19XELIlEZP9wnJl7G0WS84JtESbVwcBrije/U=',
    email_hash = '0c4244e57ae8c10b001c0964b8d4f20ad629a8f91a82f5fcac125cff2997610f'
WHERE id = '65d4f763-c25d-49e9-9791-01bc25df3ebd';

UPDATE public.users SET 
    email = 'iZzmrdo0ymp9mfMeyuM1VfvGIGfAbzrJZaxjxsLDg5jIGanEzUSI7secX6JXX+DFsGE8Cf1tBmAE8g==',
    email_hash = 'db3686c8e2c585cb5b9b73a5deec00a54ad7e68e48480b9cec3d808ff2f9a16c'
WHERE id = '06e9e861-2aec-4121-8903-39ced9904679';

UPDATE public.users SET 
    email = 'hqRwlE8hv+IP75LtFYA03Vsdpu7n1ZTfnLjQ+/PmGMhKMX53DvGXc4CY3GlWqw==',
    email_hash = 'b43e97bff3f8a42c7dfb3ef2b479fa53056989db6c4de101d4259e859e8fcf8c'
WHERE id = 'ba444ea6-bb98-4024-ae0d-510fa3b10b43';

UPDATE public.users SET 
    email = 'mJtK2wtpfXtWrI8cfLos/Ler9ikE/wqUBxLcC5ro/IXZtepfMZ+bkLu+ZQ==',
    email_hash = 'fcc40fbb8854838504699c62283f95ebbbfea853764cbc2ee21c1df7c32afbc5'
WHERE id = '3e222f72-735a-4ba5-86dc-302c42c0e484';

UPDATE public.users SET 
    email = 'Ta6f9xkFZfDR976UFonvyb95013WdD400ipEE+8pnDEEH1/Zj1M+W7RrOxiMk44=',
    email_hash = 'b8c198ddced8470e7076e5e552577cf63395b1ce3ae18595e68e1e3e95e590c1'
WHERE id = '3fdbc133-a252-44df-a5b2-ef19047919c9';

UPDATE public.users SET 
    email = 'iyy29q2gNNR7+j+Il3xA3Mfb0JodppxVc2hjF4nINbUoa0j8RhBmEbsBFPrFYEkRiu3riMxtvpO9Sw==',
    email_hash = 'c43fe3a45eec4ba8055ec2e92e0ae5771b9c03bd574198de8d90eb2d82e13ad1'
WHERE id = '8af6dc57-02a4-41db-937f-dccc6c78068c';

UPDATE public.users SET 
    email = '27UZJc8qv1ne9jaLE5nVL/udX2jqNjI+MOL71uKBlMCvoYPYhtO6nRUJSdVaJw==',
    email_hash = '004339fc435c61cc5b4e31795612a58d9b7c69030028bc731192bbf30d127d90'
WHERE id = 'b87deb15-8b04-46c1-b3d1-2b878541d34a';

UPDATE public.users SET 
    email = 'eW1EDZ365XsT4DMti0RLo3yiq23J1RjLiCboB2Qq9Y/jMZWzgEP9gqK74ie4hA0=',
    email_hash = '4d735645b29a923dad0625f5fbfce823b0f909422493b0df0091d429ea43326e'
WHERE id = 'f28cf179-08e9-44cc-93df-06f683ef35ac';

-- Note: The trigger is disabled because it tries to hash the encrypted email
-- If you want to re-enable it, you need to modify it to hash the decrypted email
-- or use a different approach for email hashing