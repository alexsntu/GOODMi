#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from PIL import Image
import os
import sys

def crop_for_telegram(input_path, output_path):
    """
    Square image for Telegram 1080x1080 px
    """
    img = Image.open(input_path)
    original_width, original_height = img.size
    print(f"Original: {original_width}x{original_height}")
    
    # Get square from smaller side
    min_side = min(original_width, original_height)
    
    # Center crop (horizontal image)
    left = (original_width - min_side) // 2
    top = 0
    right = left + min_side
    bottom = min_side
    
    cropped = img.crop((left, top, right, bottom))
    
    # Resize to 1080x1080
    final = cropped.resize((1080, 1080), Image.Resampling.LANCZOS)
    
    # Save
    final.save(output_path, quality=95)
    print(f"OK: {output_path}")
    print(f"Size: 1080x1080")

if __name__ == "__main__":
    input_file = r"C:\Users\Алекс\.cursor\projects\c-Users-GOODMi\assets\c__Users_______AppData_Roaming_Cursor_User_workspaceStorage_37bd82bdb6bde817c88473925c7cd1c3_images_456_____-436547b9-0a0f-4a4e-8695-86664997d13d.png"
    output_file = r"C:\Users\Алекс\GOODMi\Акции\maxmobiles-wallet-tg-1080x1080.png"
    
    crop_for_telegram(input_file, output_file)
