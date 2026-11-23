# Historial y procedencia

Esta carpeta conserva entregas, planes y referencias anteriores. **No es el punto de partida para implementar reglas actuales.** Primero consultar [documentación vigente](../README.md); entrar aquí solo para rastrear una decisión, prueba, migración o incidente.

Los textos «pendiente», versiones, ramas, rutas de código retiradas y resultados de pruebas describen la fecha de cada entrega. No ejecutar por iniciativa propia los SQL o procedimientos históricos. Los enlaces documentales se adaptaron a la nueva ubicación; los inventarios CSV conservaron su contenido.

## Bitácora

[PROGRESO.md](PROGRESO.md) conserva la bitácora extensa anterior. Las nuevas entregas se resumen en [Estado actual](../proyecto/ESTADO_ACTUAL.md), con el detalle del módulo correspondiente.

## MVC y Tailwind

| Entrega | Documento |
| --- | --- |
| Inventario inicial | [Fase 0](mvc-tailwind/FASE_0_INVENTARIO.md) y [rutas CSV](mvc-tailwind/FASE_0_RUTAS.csv) |
| Configuración | [Fase 1](mvc-tailwind/FASE_1_CONFIGURACION.md) |
| Enrutamiento | [Fase 2](mvc-tailwind/FASE_2_ENRUTAMIENTO.md) |
| Separación MVC | [Fase 4](mvc-tailwind/FASE_4_SEPARACION_MVC.md) |
| Retiro de puentes | [Fase 5](mvc-tailwind/FASE_5_RETIRO_PUENTES.md) y [inventario CSV](mvc-tailwind/FASE_5_ARCHIVOS_RETIRADOS.csv) |
| Tailwind | [Fase 6](mvc-tailwind/FASE_6_TAILWIND.md) |
| ZIP y hosting | [Fase 7](mvc-tailwind/FASE_7_DESPLIEGUE_HOSTINGER.md) y [assets retirados CSV](mvc-tailwind/FASE_7_ASSETS_RETIRADOS.csv) |
| Validación | [Fase 8](mvc-tailwind/FASE_8_VALIDACION_FLUJOS.md) |

## Correcciones operativas

- [Fase 3 correcciones](operativa/FASE_3_CORRECCIONES.md).
- [Fase 4 permisos para hoy e interrupciones](operativa/FASE_4_PERMISOS_MISMO_DIA.md).
- [Fase 5 recuperaciones distribuidas](operativa/FASE_5_RECUPERACION_VARIOS_DIAS.md).
- [Fase 6 tiempo excepcional y tolerancia](operativa/FASE_6_TIEMPO_EXCEPCIONAL.md).
- [Fase 7 integración operativa](operativa/FASE_7_VALIDACION_OPERATIVA.md).

## Notas

- [Fase 1 áreas y líderes](notas/FASE_1_NOTAS_AREAS_Y_LIDERES.md).
- [Fase 2 base y permisos](notas/FASE_2_NOTAS_BASE_Y_PERMISOS.md).
- [Fase 3 formularios y rúbricas](notas/FASE_3_NOTAS_FORMULARIO_Y_BORRADORES.md).
- [Fase 4 aprobación y publicación](notas/FASE_4_NOTAS_APROBACION_Y_PUBLICACION.md).
- [Fase 5 integración académica](notas/FASE_5_NOTAS_RUBRICA_E_INTEGRACION.md).
- [Fase 6 validación y despliegue](notas/FASE_6_NOTAS_VALIDACION_Y_DESPLIEGUE.md).
- [Accesos y buscador de líderes](notas/NOTAS_ACCESO_PRACTICANTES_Y_LIDERES.md).

## Planes anteriores

[MVC y Tailwind](planes/plan_implementacion_mvc_tailwind.md), [correcciones operativas](planes/PLAN_CORRECCIONES_OPERATIVAS.md) y [plan de Notas](planes/PLAN_IMPLEMENTACION_NOTAS.md). Son registro de planificación, no un estado actual ni nuevas autorizaciones de trabajo.

## Referencias e incidentes

| Documento | Uso al conservarlo |
| --- | --- |
| [Contexto original](referencias/CONTEXTO_PROYECTO.md) | Diseño inicial, decisiones y actualizaciones; contiene reglas sustituidas |
| [Documento previo de BBDD](referencias/DOCUMENTO_BBDD.md) | Procedencia del diseño; usar schema e inventario vigente para instalar |
| [Guía de pruebas XAMPP](referencias/GUIA_PRUEBAS_XAMPP.md) | Casos manuales de entregas anteriores |
| [Pruebas locales anteriores](referencias/PRUEBAS_LOCAL.md) | Ejemplos previos; no certifican el comportamiento actual |
| [Cambios de layout previos](referencias/MIS_CAMBIOS.md) | Registro anterior a Tailwind; no volver a Bootstrap por este texto |
| [Aportes de Acompañamiento](referencias/APORTES-ACOMPANAMIENTO.md) | Conservar atribución y propuestas; no son funcionalidades aprobadas por sí solas |
| [Mejoras visuales de ADMIN](visuales/MEJORAS_VISUALES_ADMIN_Y_PERMISOS.md) | Causa y comprobaciones de esa entrega |
| [Incidente MariaDB 2026-10-19](incidentes/INCIDENTE_MYSQL_LOCAL_20261019.md) | Diagnóstico, ubicación de datos/rescates y límites; no eliminar material por reorganizar documentos |

Las guías actuales de diseño y PHP local permanecen fuera del archivo histórico: [Diseño](../diseno/COMPONENTES.md), [Entornos](../operacion/ENTORNOS.md).
