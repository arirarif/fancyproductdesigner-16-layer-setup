# Fancy Product Designer - Complete BJJ GI / Jersey Custom Product Setup Guide

---

## PART 1: WHAT YOU HAVE & WHAT'S POSSIBLE

### Files You Have
- `fancy-product-designer/` — The main FPD WordPress plugin (v6.5.0)
- `shirts_jersey/` — A template package containing:
  - `product.json` — Product definition (Front + Back views, 3 SVG/PNG layers each)
  - `jersey-Front-Torso-base-1-1.svg` — Front torso shape (colorable SVG)
  - `jersey-back-Torso-1.svg` — Back torso shape (colorable SVG)
  - `base_front_sleeves_combined.svg` — Front sleeves (colorable SVG)
  - `base_back_sleeves_combined.svg` — Back sleeves (colorable SVG)
  - `jersey-Front-new-1-1.png` — Front overlay/details (locked, not colorable)
  - `jersey-back-1-1.png` — Back overlay/details (locked, not colorable)
  - Preview thumbnails

### Your Product Goal
16 customizable patch zones (8 front, 8 back) based on the IBJJF patch placement diagram:
- **Front zones 1–9**: shoulders (1,2), chest sides (3,4), belt/center (5), upper thighs (6,7), lower legs (8,9)
- **Back zones 10–16**: upper back (10), large center back (11), belt area (12), upper knees (13,14), lower legs (15,16)

### Can FPD Do This? YES — Completely

FPD supports:
- **Multi-layer views** (Front + Back as separate "views")
- **Editable text elements** — player name, number, any text
- **Upload zones** — user uploads their own logo/image to a specific patch area
- **Bounding boxes** — restrict where user can place/resize an element
- **Color-linked layers** — change jersey color from a palette
- **Print-ready export** with exact bounding box output

---

## PART 2: WHY YOUR IMPORT "DIDN'T WORK PROPERLY"

The `product.json` from the shirts_jersey package only contains **3 base layers per view** (the visual jersey shape layers). It does NOT include:
- Any text elements (name, number)
- Any upload zones (logo patches)
- Any bounding boxes for the patch areas

The official demo (`fancyproductdesigner.com/product/football-jersey-plus/`) shows these features because their demo product has ADDITIONAL elements added on top of the base layers. The template you bought is just the visual base — you need to add all 16 customizable zones yourself through the FPD admin panel (or by editing the JSON).

This is normal — you bought the template artwork, not a fully configured product. This guide shows you exactly how to configure it.

---

## PART 3: INSTALLATION

### Step 1: Install WordPress + WooCommerce
- WordPress installed at your domain
- WooCommerce plugin activated

### Step 2: Install Fancy Product Designer
1. Go to `WordPress Admin > Plugins > Add New > Upload Plugin`
2. Upload the `fancy-product-designer` folder as a ZIP
3. Activate the plugin
4. Go to `FPD > Settings` and save defaults

### Step 3: Upload Template Assets
Upload all files from `shirts_jersey/` to your WordPress Media Library OR to a specific folder via FTP:
- Recommended path: `wp-content/uploads/fpd-templates/jersey/`
- Files to upload:
  - `jersey-Front-Torso-base-1-1.svg`
  - `jersey-back-Torso-1.svg`
  - `base_front_sleeves_combined.svg`
  - `base_back_sleeves_combined.svg`
  - `jersey-Front-new-1-1.png`
  - `jersey-back-1-1.png`
  - `preview_jersey_front-3.png`
  - `preview_jersey_back-2.png`

---

## PART 4: CREATING THE FPD PRODUCT

### Step 1: Create a New FPD Product
1. Go to `FPD > Products > Add New`
2. Title: "Custom BJJ GI" (or your product name)
3. Set canvas: Width = 600, Height = 660

### Step 2: Add "Front" View
1. Click `Add View`, name it "Front"
2. Set thumbnail to `preview_jersey_front-3.png`
3. Set output dimensions: Width=240, Height=400
4. Enable "Use Printing Box as Bounding Box"
5. Printing box: left=190, top=165, width=226, height=376

### Step 3: Add Base Layers to Front View (in this exact order)

#### Layer 1 — Base Torso (colorable SVG)
- Type: Image
- Source: `jersey-Front-Torso-base-1-1.svg`
- Position: left=300, top=330, originX=center, originY=center
- Scale: 0.763 (both X and Y)
- Fill color: `#00a1df` (default jersey color)
- Colors: `1` (enables color picker)
- colorLinkGroup: `color-base` (links to other views)
- draggable=OFF, rotatable=OFF, resizable=OFF, removable=OFF
- zChangeable=OFF, topped=OFF

#### Layer 2 — Base Sleeves (colorable SVG)
- Type: Image
- Source: `base_front_sleeves_combined.svg`
- Position: left=301, top=205, originX=center, originY=center
- Scale: 0.7626
- Fill color: `#2c6bb4`
- Colors: `1`
- colorLinkGroup: `color-sleeves`
- draggable=OFF, rotatable=OFF, resizable=OFF, removable=OFF

#### Layer 3 — Overlay (locked PNG, export excluded)
- Type: Image
- Source: `jersey-Front-new-1-1.png`
- Position: left=300, top=330, originX=center, originY=center
- Scale: 0.7645
- topped=ON (always on top)
- locked=ON
- excludeFromExport=ON (this is the visual detail layer, not printed)
- draggable=OFF, rotatable=OFF, resizable=OFF, removable=OFF

---

## PART 5: ADDING THE 16 EDITABLE PATCH ZONES

This is the KEY section that the imported product.json is missing.

### Understanding Coordinates
The canvas is 600×660 pixels. The jersey occupies roughly:
- Front torso printable area: x=190–416, y=165–541
- Back torso printable area: x=193–401, y=121–467

Use these coordinates as reference when positioning elements.

### Two Types of Editable Elements

**Type A — Text Element** (for player name, number):
```json
{
  "title": "Player Name",
  "source": "Your Name",
  "type": "text",
  "parameters": {
    "left": 300,
    "top": 230,
    "originX": "center",
    "originY": "center",
    "fontSize": 28,
    "fontFamily": "Arial",
    "fill": "#ffffff",
    "editable": true,
    "draggable": false,
    "rotatable": false,
    "resizable": false,
    "removable": false,
    "colors": "#ffffff,#000000,#ff0000",
    "maxLength": 20,
    "boundingBox": { "x": 210, "y": 180, "width": 180, "height": 60 },
    "boundingBoxMode": "inside",
    "textAlign": "center",
    "z": 10
  }
}
```

**Type B — Upload Zone** (for logo/patch images):
```json
{
  "title": "Zone 1 - Right Shoulder",
  "source": "",
  "type": "image",
  "parameters": {
    "left": 230,
    "top": 200,
    "originX": "center",
    "originY": "center",
    "uploadZone": true,
    "uploadZoneMovable": false,
    "draggable": false,
    "rotatable": false,
    "resizable": false,
    "removable": true,
    "boundingBox": { "x": 190, "y": 175, "width": 80, "height": 50 },
    "boundingBoxMode": "inside",
    "z": 5
  }
}
```

---

## PART 6: COMPLETE ELEMENT MAP FOR ALL 16 ZONES

Based on the IBJJF patch placement diagram (600×660 canvas):

### FRONT VIEW — Zones 1–9

| Zone | Description       | Type        | Approx BBox (x, y, w, h)         |
|------|-------------------|-------------|-----------------------------------|
| 1    | Right shoulder    | Upload Zone | x=190, y=175, w=90, h=45         |
| 2    | Left shoulder     | Upload Zone | x=330, y=175, w=90, h=45         |
| 3    | Right chest side  | Upload Zone | x=195, y=225, w=80, h=90         |
| 4    | Center chest      | Upload/Text | x=280, y=225, w=70, h=90         |
| 5    | Belt/center       | Upload Zone | x=245, y=345, w=100, h=40        |
| 6    | Right upper thigh | Upload Zone | x=205, y=405, w=80, h=65         |
| 7    | Left upper thigh  | Upload Zone | x=310, y=405, w=80, h=65         |
| 8    | Right lower leg   | Upload Zone | x=205, y=478, w=75, h=60         |
| 9    | Left lower leg    | Upload Zone | x=315, y=478, w=75, h=60         |

### BACK VIEW — Zones 10–16

| Zone | Description       | Type        | Approx BBox (x, y, w, h)         |
|------|-------------------|-------------|-----------------------------------|
| 10   | Upper back        | Text/Upload | x=220, y=140, w=155, h=50        |
| 11   | Center back large | Text/Upload | x=210, y=200, w=175, h=120       |
| 12   | Belt back         | Text/Upload | x=225, y=330, w=145, h=40        |
| 13   | Right upper knee  | Upload Zone | x=205, y=380, w=75, h=55         |
| 14   | Left upper knee   | Upload Zone | x=315, y=380, w=75, h=55         |
| 15   | Right lower leg   | Upload Zone | x=205, y=440, w=70, h=55         |
| 16   | Left lower leg    | Upload Zone | x=318, y=440, w=70, h=55         |

**Note**: Zone 10 and 11 are typically the "Player Name" and "Number" zones. Use text elements for these.

---

## PART 7: THE COMPLETE PRODUCT.JSON (WITH ALL ZONES)

Save this as your product JSON. Replace image URLs with your actual uploaded file paths.

```json
{
  "title": "Custom BJJ GI",
  "thumbnail": "preview_jersey_front-2.png",
  "options": {
    "stageWidth": "600",
    "stageHeight": "660"
  },
  "views": [
    {
      "title": "Front",
      "thumbnail": "preview_jersey_front-3.png",
      "elements": [
        {
          "title": "Base Torso",
          "source": "jersey-Front-Torso-base-1-1.svg",
          "type": "image",
          "parameters": {
            "left": 300, "top": 330,
            "originX": "center", "originY": "center",
            "z": -1, "fill": "#00a1df", "colors": 1,
            "colorLinkGroup": "color-base",
            "draggable": 0, "rotatable": 0, "resizable": 0,
            "removable": 0, "zChangeable": 0, "topped": 0,
            "scaleX": 0.763, "scaleY": 0.763,
            "opacity": 1, "excludeFromExport": 0, "locked": 0
          }
        },
        {
          "title": "Base Sleeves",
          "source": "base_front_sleeves_combined.svg",
          "type": "image",
          "parameters": {
            "left": 301, "top": 205,
            "originX": "center", "originY": "center",
            "z": -1, "fill": "#2c6bb4", "colors": 1,
            "colorLinkGroup": "color-sleeves",
            "draggable": 0, "rotatable": 0, "resizable": 0,
            "removable": 0, "zChangeable": 0, "topped": 0,
            "scaleX": 0.7626, "scaleY": 0.7626, "opacity": 1
          }
        },
        {
          "title": "Zone 1 - Right Shoulder Logo",
          "source": "",
          "type": "image",
          "parameters": {
            "left": 235, "top": 198,
            "originX": "center", "originY": "center",
            "uploadZone": true, "uploadZoneMovable": false,
            "draggable": false, "rotatable": false,
            "resizable": false, "removable": true,
            "boundingBox": { "x": 190, "y": 175, "width": 90, "height": 45 },
            "boundingBoxMode": "inside", "z": 5, "opacity": 1
          }
        },
        {
          "title": "Zone 2 - Left Shoulder Logo",
          "source": "",
          "type": "image",
          "parameters": {
            "left": 375, "top": 198,
            "originX": "center", "originY": "center",
            "uploadZone": true, "uploadZoneMovable": false,
            "draggable": false, "rotatable": false,
            "resizable": false, "removable": true,
            "boundingBox": { "x": 330, "y": 175, "width": 90, "height": 45 },
            "boundingBoxMode": "inside", "z": 5, "opacity": 1
          }
        },
        {
          "title": "Zone 3 - Right Chest Logo",
          "source": "",
          "type": "image",
          "parameters": {
            "left": 235, "top": 270,
            "originX": "center", "originY": "center",
            "uploadZone": true, "uploadZoneMovable": false,
            "draggable": false, "rotatable": false,
            "resizable": false, "removable": true,
            "boundingBox": { "x": 195, "y": 225, "width": 80, "height": 90 },
            "boundingBoxMode": "inside", "z": 5, "opacity": 1
          }
        },
        {
          "title": "Zone 4 - Center Chest Logo",
          "source": "",
          "type": "image",
          "parameters": {
            "left": 315, "top": 270,
            "originX": "center", "originY": "center",
            "uploadZone": true, "uploadZoneMovable": false,
            "draggable": false, "rotatable": false,
            "resizable": false, "removable": true,
            "boundingBox": { "x": 280, "y": 225, "width": 70, "height": 90 },
            "boundingBoxMode": "inside", "z": 5, "opacity": 1
          }
        },
        {
          "title": "Zone 5 - Belt Center Logo",
          "source": "",
          "type": "image",
          "parameters": {
            "left": 295, "top": 365,
            "originX": "center", "originY": "center",
            "uploadZone": true, "uploadZoneMovable": false,
            "draggable": false, "rotatable": false,
            "resizable": false, "removable": true,
            "boundingBox": { "x": 245, "y": 345, "width": 100, "height": 40 },
            "boundingBoxMode": "inside", "z": 5, "opacity": 1
          }
        },
        {
          "title": "Zone 6 - Right Upper Thigh",
          "source": "",
          "type": "image",
          "parameters": {
            "left": 245, "top": 438,
            "originX": "center", "originY": "center",
            "uploadZone": true, "uploadZoneMovable": false,
            "draggable": false, "rotatable": false,
            "resizable": false, "removable": true,
            "boundingBox": { "x": 205, "y": 405, "width": 80, "height": 65 },
            "boundingBoxMode": "inside", "z": 5, "opacity": 1
          }
        },
        {
          "title": "Zone 7 - Left Upper Thigh",
          "source": "",
          "type": "image",
          "parameters": {
            "left": 350, "top": 438,
            "originX": "center", "originY": "center",
            "uploadZone": true, "uploadZoneMovable": false,
            "draggable": false, "rotatable": false,
            "resizable": false, "removable": true,
            "boundingBox": { "x": 310, "y": 405, "width": 80, "height": 65 },
            "boundingBoxMode": "inside", "z": 5, "opacity": 1
          }
        },
        {
          "title": "Zone 8 - Right Lower Leg",
          "source": "",
          "type": "image",
          "parameters": {
            "left": 243, "top": 508,
            "originX": "center", "originY": "center",
            "uploadZone": true, "uploadZoneMovable": false,
            "draggable": false, "rotatable": false,
            "resizable": false, "removable": true,
            "boundingBox": { "x": 205, "y": 478, "width": 75, "height": 60 },
            "boundingBoxMode": "inside", "z": 5, "opacity": 1
          }
        },
        {
          "title": "Zone 9 - Left Lower Leg",
          "source": "",
          "type": "image",
          "parameters": {
            "left": 353, "top": 508,
            "originX": "center", "originY": "center",
            "uploadZone": true, "uploadZoneMovable": false,
            "draggable": false, "rotatable": false,
            "resizable": false, "removable": true,
            "boundingBox": { "x": 315, "y": 478, "width": 75, "height": 60 },
            "boundingBoxMode": "inside", "z": 5, "opacity": 1
          }
        },
        {
          "title": "Overlay",
          "source": "jersey-Front-new-1-1.png",
          "type": "image",
          "parameters": {
            "left": 300, "top": 330,
            "originX": "center", "originY": "center",
            "z": 99, "fill": false, "colors": 0,
            "draggable": 0, "rotatable": 0, "resizable": 0,
            "removable": 0, "zChangeable": false,
            "scaleX": 0.7645, "scaleY": 0.7645,
            "topped": 1, "opacity": 1,
            "excludeFromExport": 1, "locked": 1
          }
        }
      ],
      "options": {
        "output_format": "man",
        "output_width": "240",
        "output_height": "400",
        "use_printing_box_as_bounding_box": "yes",
        "printing_box": {
          "left": 190, "top": 165, "width": 226, "height": 376
        }
      }
    },
    {
      "title": "Back",
      "thumbnail": "preview_jersey_back-2.png",
      "elements": [
        {
          "title": "Base Torso",
          "source": "jersey-back-Torso-1.svg",
          "type": "image",
          "parameters": {
            "left": 300, "top": 330,
            "originX": "center", "originY": "center",
            "z": -1, "fill": "#00a1df", "colors": 1,
            "colorLinkGroup": "color-base",
            "draggable": 0, "rotatable": 0, "resizable": 0,
            "removable": 0, "zChangeable": 0, "topped": 0,
            "scaleX": 0.7656, "scaleY": 0.7656, "opacity": 1
          }
        },
        {
          "title": "Base Sleeves",
          "source": "base_back_sleeves_combined.svg",
          "type": "image",
          "parameters": {
            "left": 298, "top": 206,
            "originX": "center", "originY": "center",
            "z": -1, "fill": "#2c6bb4", "colors": 1,
            "colorLinkGroup": "color-sleeves",
            "draggable": 0, "rotatable": 0, "resizable": 0,
            "removable": 0, "zChangeable": 0, "topped": 0,
            "scaleX": 0.7622, "scaleY": 0.7622, "opacity": 1
          }
        },
        {
          "title": "Zone 10 - Upper Back Text (Player Name)",
          "source": "Your Name",
          "type": "text",
          "parameters": {
            "left": 297, "top": 165,
            "originX": "center", "originY": "center",
            "z": 5,
            "fontSize": 30,
            "fontFamily": "Arial",
            "fill": "#ffffff",
            "editable": true,
            "draggable": false,
            "rotatable": false,
            "resizable": false,
            "removable": false,
            "colors": "#ffffff,#000000,#ff0000,#0000ff,#ffff00",
            "maxLength": 20,
            "boundingBox": { "x": 220, "y": 140, "width": 155, "height": 50 },
            "boundingBoxMode": "inside",
            "textAlign": "center",
            "fontWeight": "bold",
            "textTransform": "uppercase"
          }
        },
        {
          "title": "Zone 11 - Center Back Number",
          "source": "10",
          "type": "text",
          "parameters": {
            "left": 297, "top": 260,
            "originX": "center", "originY": "center",
            "z": 5,
            "fontSize": 80,
            "fontFamily": "Arial",
            "fill": "#ffffff",
            "editable": true,
            "draggable": false,
            "rotatable": false,
            "resizable": false,
            "removable": false,
            "colors": "#ffffff,#000000,#ff0000,#0000ff,#ffff00",
            "maxLength": 3,
            "boundingBox": { "x": 210, "y": 200, "width": 175, "height": 120 },
            "boundingBoxMode": "inside",
            "textAlign": "center",
            "fontWeight": "bold",
            "numberPlaceholder": true
          }
        },
        {
          "title": "Zone 12 - Belt Back Logo",
          "source": "",
          "type": "image",
          "parameters": {
            "left": 297, "top": 350,
            "originX": "center", "originY": "center",
            "uploadZone": true, "uploadZoneMovable": false,
            "draggable": false, "rotatable": false,
            "resizable": false, "removable": true,
            "boundingBox": { "x": 225, "y": 330, "width": 145, "height": 40 },
            "boundingBoxMode": "inside", "z": 5, "opacity": 1
          }
        },
        {
          "title": "Zone 13 - Right Upper Knee",
          "source": "",
          "type": "image",
          "parameters": {
            "left": 243, "top": 408,
            "originX": "center", "originY": "center",
            "uploadZone": true, "uploadZoneMovable": false,
            "draggable": false, "rotatable": false,
            "resizable": false, "removable": true,
            "boundingBox": { "x": 205, "y": 380, "width": 75, "height": 55 },
            "boundingBoxMode": "inside", "z": 5, "opacity": 1
          }
        },
        {
          "title": "Zone 14 - Left Upper Knee",
          "source": "",
          "type": "image",
          "parameters": {
            "left": 353, "top": 408,
            "originX": "center", "originY": "center",
            "uploadZone": true, "uploadZoneMovable": false,
            "draggable": false, "rotatable": false,
            "resizable": false, "removable": true,
            "boundingBox": { "x": 315, "y": 380, "width": 75, "height": 55 },
            "boundingBoxMode": "inside", "z": 5, "opacity": 1
          }
        },
        {
          "title": "Zone 15 - Right Lower Leg",
          "source": "",
          "type": "image",
          "parameters": {
            "left": 240, "top": 468,
            "originX": "center", "originY": "center",
            "uploadZone": true, "uploadZoneMovable": false,
            "draggable": false, "rotatable": false,
            "resizable": false, "removable": true,
            "boundingBox": { "x": 205, "y": 440, "width": 70, "height": 55 },
            "boundingBoxMode": "inside", "z": 5, "opacity": 1
          }
        },
        {
          "title": "Zone 16 - Left Lower Leg",
          "source": "",
          "type": "image",
          "parameters": {
            "left": 353, "top": 468,
            "originX": "center", "originY": "center",
            "uploadZone": true, "uploadZoneMovable": false,
            "draggable": false, "rotatable": false,
            "resizable": false, "removable": true,
            "boundingBox": { "x": 318, "y": 440, "width": 70, "height": 55 },
            "boundingBoxMode": "inside", "z": 5, "opacity": 1
          }
        },
        {
          "title": "Overlay",
          "source": "jersey-back-1-1.png",
          "type": "image",
          "parameters": {
            "left": 298, "top": 329,
            "originX": "center", "originY": "center",
            "z": 99, "fill": false, "colors": 0,
            "draggable": 0, "rotatable": 0, "resizable": 0,
            "removable": 0, "zChangeable": false,
            "scaleX": 0.7638, "scaleY": 0.7638,
            "topped": 1, "opacity": 1,
            "excludeFromExport": 1, "locked": 1
          }
        }
      ],
      "options": {
        "output_format": "man",
        "output_width": "240",
        "output_height": "400",
        "use_printing_box_as_bounding_box": "yes",
        "printing_box": {
          "left": 193, "top": 121, "width": 208, "height": 346
        }
      }
    }
  ]
}
```

---

## PART 8: IMPORTING THE PRODUCT INTO FPD

### Method A: Import via FPD Admin (Recommended)
1. Go to `FPD > Products > Import/Export`
2. Upload/paste the complete JSON above
3. When asked about image sources, map them to your uploaded files in Media Library
4. Click "Import"

### Method B: Manual Creation via FPD Admin UI
1. Go to `FPD > Products > Add New`
2. Add "Front" view
3. For each element in the Front view:
   - Click "Add Element"
   - Choose type (Image / Text)
   - Fill in all parameters from the JSON above
4. Repeat for "Back" view
5. Save

---

## PART 9: FIX BOUNDING BOX COORDINATES (IMPORTANT)

The bounding box coordinates in this guide are APPROXIMATE. After import, you MUST fine-tune them:

1. Go to `FPD > Products`, open your product
2. Click on each zone element
3. Use the "Bounding Box" settings to visually adjust the zone boundaries
4. Enable "Show Bounding Box" to see the zone highlighted on the canvas
5. Drag the corners to match the actual patch areas on the jersey image

The visual adjustment is the most accurate way because it lets you align the zone boxes with the actual jersey artwork pixels.

---

## PART 10: LINK FPD PRODUCT TO WOOCOMMERCE PRODUCT

1. Go to `WooCommerce > Products > Add New` (or edit existing product)
2. In the product data panel, find the "Fancy Product Designer" tab
3. Select your FPD product: "Custom BJJ GI"
4. Check "Enable Fancy Product Designer"
5. Set pricing options if needed (per-zone pricing is supported)
6. Publish the product

---

## PART 11: ELEMENT PARAMETER REFERENCE

### For Upload Zones (image patches)
| Parameter | Value | Purpose |
|-----------|-------|---------|
| `uploadZone` | `true` | Makes it an upload zone |
| `uploadZoneMovable` | `false` | Prevents zone from being moved |
| `boundingBox` | `{x,y,w,h}` | Restricts uploaded image to this area |
| `boundingBoxMode` | `"inside"` | Image stays inside the box |
| `removable` | `true` | User can clear the upload |
| `draggable` | `false` | Zone itself doesn't move |

### For Text Elements (name, number)
| Parameter | Value | Purpose |
|-----------|-------|---------|
| `editable` | `true` | User can double-click to edit |
| `maxLength` | `20` | Limit characters |
| `colors` | `"#fff,#000,..."` | Color picker options |
| `fontWeight` | `"bold"` | Font weight |
| `textTransform` | `"uppercase"` | Force uppercase |
| `numberPlaceholder` | `true` | For number fields |
| `boundingBox` | `{x,y,w,h}` | Keeps text in zone |

### Color Linking (jersey base color changes both views)
| Parameter | Value | Purpose |
|-----------|-------|---------|
| `colorLinkGroup` | `"color-base"` | All layers with same group change together |
| `colors` | `1` | Enable color picker for this element |
| `fill` | `"#00a1df"` | Default color |
| `replaceInAllViews` | `1` | Color change applies to Front AND Back |

---

## PART 12: TROUBLESHOOTING

### Problem: Import doesn't show text/upload zones
**Fix**: The original `product.json` from shirts_jersey only has base image layers. Use the COMPLETE JSON from Part 7 instead.

### Problem: Upload zones appear in wrong position
**Fix**: The coordinates in Part 7 are approximate. Use FPD Admin > visual editor to drag bounding boxes to correct positions over the jersey artwork.

### Problem: Jersey base color not changing both views
**Fix**: Ensure both Front and Back "Base Torso" elements have the SAME `colorLinkGroup` value (`"color-base"`) and `replaceInAllViews: 1`.

### Problem: Overlay PNG blocks uploads from showing
**Fix**: Ensure the Overlay PNG has `topped: 1` AND `excludeFromExport: 1`. It sits visually on top but doesn't affect the export. Upload zones must have a `z` value lower than 99 (the overlay's z).

### Problem: Player name/number text too small or overflows
**Fix**: Adjust `fontSize` and the `boundingBox` `width`/`height` values. Enable `widthFontSize` to auto-scale text to fit the bounding box width.

### Problem: Images from official demo not matching your import
**Fix**: The official demo uses a different, more complete product configuration. Your purchased template only provides the artwork files — you must build the interactive layers yourself using this guide.

---

## PART 13: RECOMMENDED WORKFLOW SUMMARY

```
1. Install WordPress + WooCommerce
2. Install fancy-product-designer plugin (ZIP upload)
3. Upload all shirts_jersey files to wp-content/uploads/fpd-templates/jersey/
4. Go to FPD > Products > Import
5. Import the COMPLETE JSON from Part 7 (with all 16 zones)
6. Update all image source paths to match your uploaded file URLs
7. Go to FPD > Products > Edit your product
8. For each of the 16 zones, visually fine-tune bounding box positions
9. Create a WooCommerce product and link the FPD product to it
10. Test on frontend: verify all zones are clickable/editable
11. Place a test order to confirm order data saves correctly
```

---

## PART 14: YOUR IMAGE vs. THE TEMPLATE

**Important clarification**: Your reference image shows a **BJJ GI** (Brazilian Jiu-Jitsu uniform) — not a standard football jersey. The `shirts_jersey` template you purchased is a **football jersey** shape.

This means:
- **Option A (Quick)**: Use the football jersey template as-is and apply the 16 zones to it. It won't perfectly match the BJJ GI shape but will work as a functional product.
- **Option B (Accurate)**: Get or create BJJ GI artwork (SVG) matching your reference image, then follow this same guide substituting those SVG files as the base layers. The rest of the configuration (zones, text, upload parameters) stays identical.

For Option B SVG creation, you would need:
- A front BJJ GI silhouette SVG
- A back BJJ GI silhouette SVG
- Exported as single-color SVGs (the fill color is controlled by FPD's color picker)

---

*Guide based on: FPD Plugin v6.5.0 | Template: shirts_jersey | 16-zone IBJJF patch placement layout*
