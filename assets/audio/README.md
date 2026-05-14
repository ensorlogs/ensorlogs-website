# MP3 de los logs

Convención simple: el archivo se llama como el slug del log, ej. `wordpress-vale-pena-aprender-2026.mp3`.

- Mono 96–128 kbps suele bastar para voz. Si hay más de una voz o música, sube un poco el bitrate.
- En el front matter del `.md` va algo así:

```yaml
podcast:
  src: ../assets/audio/<slug>.mp3
  title: "Comentario del autor sobre este log"
  duration: "12:30"
  chapters:
    - { time: "0:00", title: "Intro" }
    - { time: "1:20", title: "Contexto" }
  guests:
    - { name: "Nombre", role: "Rol" }
```

En WordPress: sube el MP3 a la Mediateca y pega la URL en el campo de audio del log (no hace falta que coincida la ruta local).
