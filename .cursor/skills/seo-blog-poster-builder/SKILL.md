---
name: seo-blog-poster-builder
description: Generate 871×580 px blog post header images for GOODMi in Xiaomi style. Use when the user provides a blog article title, URL, and optionally a reference image, and wants to create a branded poster for the blog.
---

# GOODMi Blog Poster Builder

Generate professional header images (871×580 px) for GOODMi blog articles in Xiaomi's minimalist design style.

## Quick Start

When the user provides:
1. **Blog article title** — the main headline
2. **Blog article URL** (optional but helpful for context)
3. **Reference image** (optional — if provided, analyze it for design inspiration)

Generate a 871×580 px poster using the `GenerateImage` tool with the specifications below.

## Design Guidelines

### Xiaomi Style Brand Elements

- **Color palette**: Clean, modern. Primary: white/light gray backgrounds with deep blacks, occasional brand accent colors (orange, teal, purple, or tech-blue). Avoid gradients unless they enhance depth minimally.
- **Typography**: Modern sans-serif (think clean, geometric). Hierarchy: large bold title, smaller descriptive text if space permits.
- **Visual elements**: Minimalist tech graphics, subtle geometric shapes, clean lines. NO clutter — white space is a feature.
- **Xiaomi aesthetic**: Think Apple-like simplicity + tech minimalism. Product-focused if mentioned in title, or abstract tech elements.

### Image Specifications

| Aspect | Requirement |
|--------|-------------|
| **Size** | 871×580 px (exactly) |
| **Format** | PNG or JPG (suggest PNG for cleaner text) |
| **Title placement** | Top half to center, main focus |
| **Branding** | NO logo — clean design without branding |
| **Text legibility** | Must be readable on social media / search results (high contrast) |
| **Style** | Xiaomi-inspired: flat design, minimalism, tech-forward |

## Workflow

**Step 1: Extract article context**
- Read the title carefully
- Note any keywords or product mentions
- If a reference image is provided, analyze color palette, composition, mood

**Step 2: Craft the poster concept**
- Map title to a visual theme (e.g., "Xiaomi 15 Pro Camera Tips" → sleek phone + lens elements)
- Plan color scheme (neutral base + 1–2 accent colors)
- Decide text layout: centered title, or title + subtitle

**Step 3: Generate the image**
- Use `GenerateImage` tool with detailed, specific description
- Include: exact dimensions (871×580), Xiaomi style cues, color palette, layout, main text
- Example prompt structure:
  ```
  Create a 871×580 px header image in Xiaomi minimalist style for a blog post about [TITLE].
  
  Design: [describe layout, colors, visual elements]
  
  Text: Main title "[TITLE]" in white/bold sans-serif at [position].
  
  Style: Clean, modern, flat design. Minimal geometric accents. Tech-forward aesthetic.
  NO branding or logos on the image.
  ```

**Step 4: Deliver**
- Return the generated image
- Briefly describe the design choice (colors, composition rationale)
- Offer revisions if needed (different color, layout, mood)

## Examples

### Example 1: Product Review Blog
**Input:**
- Title: "Xiaomi 15 Pro: полный обзор камеры Leica 50 Мп"
- No reference image

**Design approach:**
- Background: Deep dark gray/black with teal accent
- Visual: Sleek phone silhouette + camera lens icon, minimalist line art
- Title: White bold sans-serif, centered
- Mood: Premium, professional

---

### Example 2: Tips/Guide Blog
**Input:**
- Title: "10 советов по фотографии на Xiaomi"
- Reference image: Photo with warm outdoor lighting

**Design approach:**
- Background: Soft white with warm accent (gold/orange)
- Visual: Camera viewfinder + abstract geometric shapes
- Title: Dark text, modern layout
- Mood: Friendly, helpful, approachable

---

## Notes

- If the title is very long, consider abbreviating on the poster or using a shorter version
- Keep text minimal — ideally just the title or title + 1–2 word tagline
- Always ensure text contrast meets accessibility standards (dark on light, or light on dark)
- If you're unsure about a color or layout, default to: white background + dark text + one teal or blue accent
- NO logos or branding marks — clean, content-focused design only

## Save & Naming Convention

Save generated images in the blog folder with naming:
```
POSTER-[SHORT-TITLE]-GOODMI.png
```

Example: `POSTER-XIAOMI-15-PRO-CAMERA-GOODMI.png`

---

**Need revisions?** After generating, ask the user:
- Different color scheme?
- Adjust text layout?
- Change visual style (more abstract vs. product-focused)?
- **Branding visibility (more/less / none)?** → Always no logos
