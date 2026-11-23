> **Archivo histórico.** Conserva decisiones y evidencias de su entrega; no define por sí solo el estado actual. Para trabajar hoy, empezar por [el índice de documentación](../../README.md). Las reglas vigentes están en proyecto, arquitectura y módulos.

# Aportaciones realizadas por Julio

- Creación del módulo de acompañamiento utilizando Ajax y Bootstrap, permitiendo que los cambios se reflejen en la base de datos sin necesidad de recargar la página y manteniendo el foco en el perfil del estudiante seleccionado tras la búsqueda.

- Para la sección de monitoreo se desarrolló una maqueta inspirada en la metodología Kanban, tomando como referencia la interfaz de Trello.

# Sugerencias para futuros colaboradores

- Mantener una arquitectura modular, separando la lógica de negocio del acceso a la base de datos para facilitar el mantenimiento y la escalabilidad.

- El módulo de marcaje cumple correctamente con el registro de asistencia en condiciones normales. Sin embargo, debido a errores humanos (olvido de marcar entrada o salida, marcajes duplicados, entre otros), se recomienda incorporar un módulo de gestión de excepciones que permita al personal de Recursos Humanos corregir registros mediante un token de autorización, manteniendo una trazabilidad completa de las modificaciones (usuario, fecha, hora, motivo y registro original).

- Encriptar los datos críticos con el objetivo de mejorar la seguridad del sistema.

- Crear un perfil de usuario dedicado exclusivamente a la gestión de Recursos Humanos.

# Directriz general

Como principio general, se propone que la arquitectura y los cambios implementados prioricen las siguientes características:

- **Seguridad**
- **Mantenibilidad**
- **Heredabilidad**

Esto permitirá facilitar el mantenimiento del sistema considerando la alta rotación de personal.

# Colaboradores

- Marcos
- Luis
- Julio