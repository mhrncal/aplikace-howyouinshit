#!/bin/bash
# Vyčištění zamrzlých PHP sessions

echo "🔧 ODMRZNUTÍ APLIKACE"
echo "===================="
echo ""

SESSION_DIR="/srv/app/storage/sessions"

if [ ! -d "$SESSION_DIR" ]; then
    echo "❌ Složka $SESSION_DIR neexistuje!"
    exit 1
fi

echo "📁 Session složka: $SESSION_DIR"
echo ""

# Spočítej session soubory
COUNT=$(ls -1 $SESSION_DIR/sess_* 2>/dev/null | wc -l)

if [ $COUNT -eq 0 ]; then
    echo "✅ Žádné session soubory k smazání"
    exit 0
fi

echo "🗑️  Našel jsem $COUNT session souborů"
echo ""
echo "Smažu je? (y/n)"
read -r response

if [[ "$response" =~ ^[Yy]$ ]]; then
    rm -f $SESSION_DIR/sess_*
    echo ""
    echo "✅ HOTOVO! Session soubory smazány"
    echo ""
    echo "Nyní:"
    echo "1. Otevři prohlížeč"
    echo "2. Refresh stránku (Ctrl+F5)"
    echo "3. Přihlaš se znovu"
else
    echo "❌ Zrušeno"
fi
