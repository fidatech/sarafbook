#!/usr/bin/env bash
# Prepares the SSH connection "production" on the GitHub runner from the PROD_SSH_* secrets.
set -Eeuo pipefail
umask 077
mkdir -p "$HOME/.ssh"
printf '%s\n' "$PROD_SSH_KEY" | tr -d '\r' > "$HOME/.ssh/production"
chmod 600 "$HOME/.ssh/production"
port="${PROD_SSH_PORT:-22}"
ssh-keyscan -T 15 -p "$port" "$PROD_SSH_HOST" >> "$HOME/.ssh/known_hosts" 2>/dev/null
[ -s "$HOME/.ssh/known_hosts" ] || { echo "::error::The server did not answer on port $port. Check PROD_SSH_HOST and PROD_SSH_PORT."; exit 1; }
cat > "$HOME/.ssh/config" <<CONFIG
Host production
  HostName $PROD_SSH_HOST
  Port $port
  User $PROD_SSH_USER
  IdentityFile $HOME/.ssh/production
  BatchMode yes
  IdentitiesOnly yes
  ConnectTimeout 20
  ServerAliveInterval 30
CONFIG
ssh production 'echo "Connected to $(hostname) as $(whoami)"' | sed -E 's/as .*/as the cPanel user/'
