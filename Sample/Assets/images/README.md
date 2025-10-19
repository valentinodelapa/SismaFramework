# SismaFramework - Immagini Social e Favicon

Questa cartella contiene le immagini per SEO, social media e branding.

## File creati

### Favicon
- `favicon.svg` - Favicon SVG vettoriale (consigliato per browser moderni)

### Immagini Social (formato SVG sorgente)
- `sisma-og-image.svg` - Open Graph image (1200x630px) per Facebook/LinkedIn
- `sisma-twitter-card.svg` - Twitter Card image (1200x600px)

## Conversione da SVG a PNG

Per convertire i file SVG in PNG, puoi usare uno di questi metodi:

### Metodo 1: ImageMagick (consigliato)

```bash
# Installa ImageMagick se non lo hai già
# Windows: https://imagemagick.org/script/download.php
# Linux: apt-get install imagemagick
# macOS: brew install imagemagick

# Converti Open Graph image
magick sisma-og-image.svg -resize 1200x630 sisma-og-image.png

# Converti Twitter Card
magick sisma-twitter-card.svg -resize 1200x600 sisma-twitter-card.png

# Crea favicon PNG in diverse dimensioni
magick favicon.svg -resize 32x32 favicon-32x32.png
magick favicon.svg -resize 16x16 favicon-16x16.png
magick favicon.svg -resize 180x180 apple-touch-icon.png
```

### Metodo 2: Inkscape

```bash
# Installa Inkscape: https://inkscape.org/

# Converti con Inkscape
inkscape sisma-og-image.svg --export-filename=sisma-og-image.png --export-width=1200 --export-height=630
inkscape sisma-twitter-card.svg --export-filename=sisma-twitter-card.png --export-width=1200 --export-height=600
inkscape favicon.svg --export-filename=favicon-32x32.png --export-width=32 --export-height=32
inkscape favicon.svg --export-filename=favicon-16x16.png --export-width=16 --export-height=16
inkscape favicon.svg --export-filename=apple-touch-icon.png --export-width=180 --export-height=180
```

### Metodo 3: Online (più semplice)

1. Vai su https://cloudconvert.com/svg-to-png
2. Carica i file SVG
3. Imposta le dimensioni:
   - `sisma-og-image.svg` → 1200x630px
   - `sisma-twitter-card.svg` → 1200x600px
   - `favicon.svg` → 32x32px, 16x16px, 180x180px
4. Scarica i PNG generati

### Metodo 4: GIMP

1. Apri GIMP
2. File → Apri → Seleziona il file SVG
3. Imposta le dimensioni richieste
4. File → Esporta come PNG

## File finali richiesti

Dopo la conversione, dovresti avere:

```
Sample/Assets/images/
├── favicon.svg (✓ già presente)
├── favicon-32x32.png (da generare)
├── favicon-16x16.png (da generare)
├── apple-touch-icon.png (da generare - 180x180px)
├── sisma-og-image.svg (✓ già presente)
├── sisma-og-image.png (da generare - 1200x630px)
├── sisma-twitter-card.svg (✓ già presente)
└── sisma-twitter-card.png (da generare - 1200x600px)
```

## Personalizzazione

Se vuoi modificare il design:
1. Apri i file SVG con un editor (Inkscape, Adobe Illustrator, o anche un text editor)
2. Modifica colori, testo, o layout
3. Salva e riconverti in PNG

## Note

- I file SVG utilizzano il gradient brand colors (#4a90e2 → #7b68ee)
- Le immagini social includono il logo esagonale e le feature principali
- Il favicon è semplice e riconoscibile anche a piccole dimensioni
- Ricorda di spostare questi file nella cartella `/assets/images/` del deployment
