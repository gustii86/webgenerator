#!/bin/bash
# Ejemplo de script generador. Uso: ./wix.sh <dominio>
# Crea sitios/<dominio>/index.html. Reemplázalo por tu propio wix.sh si ya tienes uno.
set -e

DOMINIO="$1"
[[ "$DOMINIO" =~ ^[a-z0-9]+$ ]] || { echo "Dominio inválido"; exit 1; }

DIR="$(cd "$(dirname "$0")" && pwd)/sitios/$DOMINIO"
mkdir -p "$DIR"

cat > "$DIR/index.html" <<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>$DOMINIO</title></head>
<body>
  <h1>Bienvenido a $DOMINIO</h1>
  <p>Web generada con webgenerator.</p>
</body>
</html>
HTML

echo "Web $DOMINIO creada"
