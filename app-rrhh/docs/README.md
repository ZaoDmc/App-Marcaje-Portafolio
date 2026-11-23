# Documentación de ePrácticas RRHH

Este es el punto de entrada para personas y agentes de IA. Explica el sistema actual y dirige al archivo que corresponde a cada cambio. Actualizado el **11 de noviembre de 2026**. Entrega de código preparada: **2.1.4**; la versión servida se obtiene de `config/version.php` y el estado productivo se registra por separado.

## Primera lectura

1. [Instrucciones de trabajo](../AGENTS.md): cómo intervenir el repositorio.
2. [Contexto del proyecto](proyecto/CONTEXTO.md): propósito, entorno y alcance.
3. [Estado y pendientes](proyecto/ESTADO_ACTUAL.md): situación actual y cambios recientes.
4. [Roles y permisos](proyecto/ROLES_Y_PERMISOS.md): quién puede hacer cada acción.
5. Leer el módulo de la tarea y su mapa de código; no hace falta cargar el historial completo.

## Dónde buscar o editar

| Necesidad | Documento vigente |
| --- | --- |
| Cambiar tamaños, fuentes, tarjetas, colores o logotipo | [Branding](diseno/BRANDING.md) |
| Proponer cambios de etiquetas y mensajes visibles | [Textos de interfaz](diseno/TEXTOS_INTERFAZ.md) |
| Entender CSS, temas, DaisyUI y compilación | [Componentes visuales](diseno/COMPONENTES.md) |
| Entender rutas, controladores, servicios y vistas | [Arquitectura](arquitectura/README.md) |
| Identificar reglas de un módulo | [Índice de módulos](modulos/README.md) |
| Instalar o evolucionar la base de datos | [Datos y migraciones](datos/README.md) |
| Preparar el entorno local y su configuración | [Entornos](operacion/ENTORNOS.md) |
| Publicar backend y frontend en Hostinger | [Despliegue](operacion/DESPLIEGUE.md) |
| Gestionar secretos y actualizaciones del motor PHP | [Seguridad operativa](operacion/SEGURIDAD.md) |
| Elegir comprobaciones sin tocar datos reales | [Pruebas](desarrollo/PRUEBAS.md) |
| Trabajar con colaboradores, ramas y PR | [Colaboración](desarrollo/COLABORACION.md) |
| Elegir e incrementar la versión del software | [Versionado](desarrollo/VERSIONADO.md) y [CHANGELOG](../CHANGELOG.md) |
| Identificar la versión efectivamente publicada | [Registro de producción](operacion/PRODUCCION.md) |
| Encontrar fases, decisiones antiguas y evidencias | [Historial](historial/README.md) |

## Qué significa editar estos archivos

Los Markdown describen reglas y textos deseados. **La aplicación no los carga para construir pantallas ni calcular horas.** Por ejemplo, cambiar `16px` por `18px` en Branding actualiza la especificación: después una persona o IA debe aplicarlo al CSS, compilar si corresponde y verificar el resultado.

Los datos administrables desde el portal —áreas, feriados, criterios de rúbrica— se cambian en sus pantallas y se guardan en la base. No se configuran editando Markdown. Véase la [guía de textos](diseno/TEXTOS_INTERFAZ.md).

## Regla para evitar contradicciones

Las instrucciones actuales del responsable prevalecen. Esta documentación separa las reglas vigentes de la historia, que conserva lo que se pensaba y probó en cada fase. Una prueba local, un plan antiguo o una frase «pendiente» en un archivo histórico no describe por sí sola la situación actual.

Los documentos vigentes representan el acuerdo y el código revisado; no certifican por sí solos el estado del hosting. Las diferencias se anotan en [Estado y pendientes](proyecto/ESTADO_ACTUAL.md). No se deben ocultar ajustando silenciosamente el software a una regla histórica.

## Estructura

```text
docs/
  README.md          Entrada y rutas de lectura
  AGENTS.md          Reglas para mantener documentación
  proyecto/          Contexto, roles y estado actual
  arquitectura/      Flujo HTTP y mapa técnico
  modulos/           Funcionalidad y reglas por módulo
  diseno/            Especificación visual y textos editables
  datos/             Esquema vigente y migraciones
  operacion/         Entornos, despliegue y seguridad
  desarrollo/        Pruebas y colaboración
  historial/         Fases, planes, referencias e incidentes
```

El README de raíz se conserva como presentación e instalación; `database/README.md` permanece junto a los SQL como inventario de migraciones. Los Markdown de dependencias no forman parte de esta organización y no deben moverse.
