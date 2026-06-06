# Reglas del Agente

## 📁 Sistema de Documentación Automática

Toda sesión de trabajo **debe documentarse en tiempo real** en la carpeta `docu/`.
No esperés al final — actualizá el archivo correspondiente **a medida que trabajás**.

---

## 🗂️ Cómo determinar el módulo

Detectá el módulo según los archivos que estás tocando:

| Si editás archivos en...       | Documentá en...              |
|-------------------------------|------------------------------|
| `**/login/**` o `*auth*`      | `docu/login/login.md`        |
| `**/dashboard/**`             | `docu/dashboard/dashboard.md`|
| `**/api/**` o `**/routes/**`  | `docu/api/api.md`            |
| `**/components/**`            | `docu/components/components.md` |
| `**/db/**` o `**/models/**`   | `docu/db/db.md`              |
| Cualquier otro                | `docu/general/general.md`    |

Si un cambio toca **múltiples módulos**, actualizá **cada archivo correspondiente**.

---

## ✍️ Qué documentar en cada .md

Cada vez que modifiques, creés o elimines algo, añadí una entrada con este formato:

```markdown
## [YYYY-MM-DD] Título corto de lo que se hizo

### Archivos tocados
- `ruta/al/archivo.ext` — qué se hizo ahí

### TODOs / Próximos pasos
- [ ] Tarea pendiente 1
- [ ] Tarea pendiente 2
```

---

## 📋 Reglas estrictas

1. **Documentá ANTES de pasar al siguiente paso**, no al final.
2. Si el archivo `docu/modulo/modulo.md` no existe, **crealo vos**.
3. Siempre actualizá `docu/_index/index.md` con un resumen de una línea del cambio.
4. Los TODOs deben ser **accionables y concretos**, no genéricos.
5. Nunca borrés entradas anteriores — solo agregás al final.



---
<!-- Reglas de documentación automática -->

# 📁 Sistema de Documentación Automática

Documentá en tiempo real en `docu/` según los archivos que tocás:

| Archivos en...            | Documentá en...                   |
|--------------------------|-----------------------------------|
| `**/login/**`, `*auth*`  | `docu/login/login.md`             |
| `**/dashboard/**`        | `docu/dashboard/dashboard.md`     |
| `**/api/**`, `**/routes/**` | `docu/api/api.md`              |
| `**/components/**`       | `docu/components/components.md`   |
| `**/db/**`, `**/models/**` | `docu/db/db.md`                |
| Cualquier otro           | `docu/general/general.md`         |

Formato de cada entrada:

```markdown
## [YYYY-MM-DD] Título

### Archivos tocados
- `ruta/archivo` — qué cambió

### TODOs / Próximos pasos
- [ ] Tarea concreta
```

Reglas: documentá ANTES de pasar al siguiente paso. Actualizá siempre `docu/_index/index.md`.
