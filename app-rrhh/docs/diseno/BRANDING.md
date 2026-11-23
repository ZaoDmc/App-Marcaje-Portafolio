# Guía visual de E-prácticas

**Estado:** Reglas confirmadas, con algunos ajustes pendientes de definir.

Este documento es la referencia para unificar el diseño del portal. Sus valores son editables: busca el apartado correspondiente y cambia únicamente el dato de la columna **Valor**. No necesitas conocer Tailwind ni escribir CSS.

**Ejemplo:** para aumentar el espacio interior de una tarjeta en móvil, ve a **Tamaño de tarjetas** y cambia **Relleno interior en móvil** de `16px` a `18px`.

Modificar este archivo actualiza la especificación; los cambios deben aplicarse posteriormente al código para verse en la interfaz. Los valores marcados como **Pendiente** todavía no son una regla aprobada.

## Índice de ajustes

- [Identidad y logotipo](#identidad-y-logotipo)
- [Fuente de los textos](#fuente-de-los-textos)
- [Tamaño de textos](#tamaño-de-textos)
- [Tamaño de tarjetas](#tamaño-de-tarjetas)
- [Colores y avisos](#colores-y-avisos)
- [Temas disponibles](#temas-disponibles)
- [Botones y campos](#botones-y-campos)
- [Tablas](#tablas)
- [Espacios de la página y móvil](#espacios-de-la-página-y-móvil)
- [Iconos y referencias](#iconos-y-referencias)

## Identidad y logotipo

El portal debe identificarse como parte de **E-prácticas**. Su apariencia será institucional, profesional y clara, adecuada para gestionar prácticas, asistencia, notas y registros. Debe combinar esa seriedad con un carácter juvenil, fluido y visualmente atractivo.

Todos los temas deben permitir una lectura cómoda. Se evitarán combinaciones molestas sin reducir el contraste necesario entre textos, controles y fondos.

| Ajuste | Valor |
| --- | --- |
| Imagen principal | `public/assets/brand/logo_rojo_blanco.png` |
| Variante del login en modo claro | `public/assets/brand/logo_rojo_negro.png`, letras negras y símbolo rojo sobre blanco. |
| Icono de pestaña del navegador | `public/assets/img/logo-sin-texto.png` |
| Ancho del logotipo | `164px` |
| Altura máxima del logotipo | `68px` |
| Proporciones | Conservar las proporciones, sin deformar la imagen. |
| Alineación | Esquina superior izquierda. |
| Ubicación | Inicio de sesión y esquina superior izquierda del portal después de ingresar. |
| Fondo detrás del logotipo | Oscuro para la variante de letras blancas; blanco para la variante negra y roja del login Claro. |
| Colores del logotipo | Conservar los originales en todos los temas. |

La imagen principal tiene fondo transparente, texto blanco y un símbolo rojo con fondo blanco. El login Claro utiliza la variante negra y roja sobre blanco, autorizada expresamente; el modo oscuro conserva la imagen principal.

## Fuente de los textos

Se utilizará **una familia tipográfica común** en login, ADMIN, SUPERVISOR y PRACTICANTE. La fuente no cambiará según el dato mostrado, la pantalla o el tema.

| Ajuste | Valor |
| --- | --- |
| Fuente principal | Segoe UI |
| Fuentes de respaldo, en orden | Helvetica Neue, Arial, sans-serif |
| Peso de texto normal | `400` |
| Peso de etiquetas y botones | `600` |
| Pesos permitidos para títulos | `600` o `700` |
| Excepciones de familia por rol o contenido | Ninguna. |

Las fuentes de respaldo solo se usan cuando la principal no está disponible en el equipo. No representan variantes elegidas para horarios, datos personales o roles diferentes. El peso `400` corresponde a texto normal; `600` y `700` dan mayor énfasis.

Queda pendiente asignar `600` o `700` a cada nivel concreto de título.

## Tamaño de textos

Para cambiar el tamaño de una letra, modifica su fila en esta tabla. Estos valores se aplican según la función del texto y se comparten entre roles.

| Texto que quieres cambiar | Valor |
| --- | --- |
| Título de página | `24px` |
| Título de sección | `20px` |
| Título de tarjeta | `16px` |
| Texto principal | `16px` |
| Etiquetas, campos y botones | `14px` a `16px` |
| Texto de tablas | `14px` |
| Ayudas y metadatos | `12px` a `13px` |
| Valor principal de un indicador | `28px` a `32px` |
| Interlineado | `1,5` veces el tamaño del texto. |

En móvil pueden adaptarse títulos e indicadores, sin reducir campos ni texto de lectura. Los tamaños fuera de esta escala requieren una razón documentada. Al implementar se convertirán a una escala común en `rem`.

Los rangos siguen aprobados como rangos. Falta asignar sus valores a variantes concretas para evitar decisiones distintas en cada pantalla.

## Tamaño de tarjetas

**Estas medidas están confirmadas.** Para cambiar el espacio interior, el redondeado o el borde de las tarjetas, modifica la fila correspondiente.

| Parte de la tarjeta que quieres cambiar | Valor |
| --- | --- |
| Relleno interior en escritorio | `24px` |
| Relleno interior en móvil | `16px` |
| Separación entre título y contenido | `16px` |
| Redondeado de esquinas | `12px` |
| Grosor del borde | `1px` |
| Color del borde | Tenue y adaptado al tema. |
| Sombra en Claro | Suave. |
| Sombra en temas oscuros | Discreta o ausente. |
| Fondo del encabezado | La misma superficie de la tarjeta. |
| Separación del encabezado | Tenue, cuando sea necesaria. |
| Ancho o altura fija de las tarjetas | Pendiente de definir; no se ha aprobado una medida fija. |

**Relleno interior** es el espacio entre el borde y el contenido. Cambiarlo hace que la tarjeta se vea más amplia o compacta; no cambia directamente el tamaño de las letras. Para eso debes modificar **Título de tarjeta** o **Texto principal** en el apartado anterior.

Las variantes definidas son:

| Variante | Uso |
| --- | --- |
| Tarjeta estándar | Presentar contenido general. |
| Tarjeta de indicador | Destacar un valor principal. |
| Panel secundario | Agrupar información dentro de otra tarjeta. |

Una misma variante debe mantener la misma apariencia en ADMIN, SUPERVISOR y PRACTICANTE.

## Colores y avisos

| Elemento | Valor |
| --- | --- |
| Color institucional principal | `#2563EB` |
| Color institucional secundario | `#1E3A8A` |
| Acento | Naranja; código exacto pendiente. |
| Éxito | Verde; código exacto pendiente. |
| Advertencia | Ámbar; código exacto pendiente. |
| Error o rechazo | Rojo; código exacto pendiente. |
| Información | Azul; código exacto pendiente. |
| Fondo de los avisos | Coloreado según el estado, con intensidad y contraste adecuados al tema. |
| Identificación del estado | Combinar icono y texto; no depender únicamente del color. |

Los fondos coloreados de los avisos deben conservar una lectura cómoda en todas las paletas, incluidos Coffee y Black. Esto permite que las señales de estado mantengan color incluso en el tema Black.

## Temas disponibles

| Ajuste | Valor |
| --- | --- |
| Temas | light, dark, synthwave, aqua, coffee y black. |
| Roles con selección de tema | ADMIN, SUPERVISOR y PRACTICANTE. |
| Preferencia guardada | Por navegador. |
| Superficies de fondo | Tres superficies de la misma paleta para distinguir fondo, tarjetas y paneles. |
| Intensidad de bordes | Tenue. |
| Elemento que conserva sus colores originales | Logotipo. |
| Superficies de Black | Negras o grises oscuras. |
| Texto de Black | Claro, manteniendo legibles las señales de estado. |

La familia tipográfica común no cambia al seleccionar otro tema.

## Botones y campos

Los botones deben ofrecer respuesta visual mediante alternancia de colores o movimiento. Los detalles siguientes no aparecen definidos en el archivo guardado y continúan pendientes.

| Ajuste que puedes definir | Valor |
| --- | --- |
| Altura de botones | Pendiente. |
| Altura de campos | Pendiente. |
| Redondeado de botones y campos | Pendiente. |
| Apariencia del botón principal | Pendiente. |
| Apariencia del botón secundario | Pendiente. |
| Apariencia de acciones destructivas | Pendiente. |
| Efecto al pasar el mouse | Pendiente. |
| Aspecto de elementos deshabilitados | Pendiente. |
| Indicador de foco al navegar con Tab | Pendiente. |
| Separación entre campos | Pendiente. |

La respuesta visual de un botón habilitado debe distinguirse del estado de un botón que no puede utilizarse; ese comportamiento todavía debe concretarse.

## Tablas

| Ajuste que puedes definir | Valor |
| --- | --- |
| Densidad: compacta, normal o amplia | Pendiente. |
| Espacio superior e inferior dentro de cada fila | Pendiente. |
| Tratamiento de textos largos | Pendiente. |
| Uso de «Ver más» para observaciones | Pendiente. |
| Comportamiento cuando una tabla no cabe | Pendiente. |

El tamaño de letra de las tablas ya está definido en **Tamaño de textos**. La separación de filas se configura aquí y no equivale al tamaño de la letra.

## Espacios de la página y móvil

| Ajuste que puedes definir | Valor |
| --- | --- |
| Margen interior de página en escritorio | Pendiente. |
| Margen interior de página en móvil | Pendiente. |
| Separación entre tarjetas | Pendiente. |
| Separación entre secciones | Pendiente. |
| Ancho máximo del contenido | Pendiente. |
| Comportamiento del menú lateral en móvil | Pendiente. |
| Información que puede aparecer plegada | Pendiente. |

Se mantiene la regla aprobada para la lectura en móvil: adaptar títulos e indicadores sin reducir campos ni texto de lectura.

## Iconos y referencias

| Ajuste que puedes definir | Valor |
| --- | --- |
| Tamaño de iconos de botones y menús | Pendiente. |
| Tamaño de iconos de indicadores | Pendiente. |
| Navegadores y dispositivos prioritarios | Pendiente. |
| Pantalla de referencia visual | Pendiente. |
| Imagen o referencia externa | Pendiente. |
| Primera pantalla para revisar el diseño unificado | Pendiente. |
| Reglas adicionales | Pendiente. |

Las imágenes presentes en el proyecto no se consideran referencias aprobadas hasta que se indiquen expresamente en esta sección.
