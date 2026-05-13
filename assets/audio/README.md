# Audios de los logs ("Comentarios del autor")

Sube aquí los MP3 que acompañan cada log. Convenciones:

- Nombre del archivo igual al slug del log: `wordpress-vale-pena-aprender-2026.mp3`.
- Formato recomendado: **MP3 mono 96–128 kbps** (voz). Si grabas con invitado,
  usa estéreo y 128–192 kbps.
- Apunta al archivo desde el front matter del Markdown:

```yaml
podcast:
  src: ../assets/audio/<slug>.mp3
  title: "Comentario del autor sobre este log"
  duration: "12:30"
  chapters:
    - { time: "0:00", title: "Intro" }
    - { time: "1:20", title: "Contexto" }
  guests:
    - { name: "Nombre invitado", role: "Rol" }
```

- En WordPress, el campo `Audio del comentario del autor` del log acepta una
  URL pública (puedes subir el MP3 a la Mediateca y pegar su URL).
