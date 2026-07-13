<?php
/**
 * Shared <head> + Tailwind config, matching the Dhaa Baja design system.
 * Usage: $pageTitle = '...'; include 'includes/head.php';
 */
?><!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title><?= e($pageTitle ?? 'Dhaa Baja | Ancestral Rhythms') ?></title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400..800;1,400..800&family=Manrope:wght@200..800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<link href="assets/css/site.css" rel="stylesheet"/>
<script id="tailwind-config">
  tailwind.config = {
    darkMode: "class",
    theme: {
      extend: {
        colors: {
          "surface-dim":"#dcd9d9","outline-variant":"#dcc0bc","inverse-primary":"#ffb4a9",
          "surface-tint":"#a03f34","surface-container-low":"#f6f3f2","surface-container-high":"#eae7e7",
          "primary-fixed-dim":"#ffb4a9","background":"#fcf9f8","surface-container":"#f0eded",
          "on-primary-fixed":"#410001","secondary-container":"#fec660","primary-container":"#7b241c",
          "secondary-fixed-dim":"#f4be59","error":"#ba1a1a","on-secondary-fixed":"#271900",
          "on-secondary":"#ffffff","on-secondary-fixed-variant":"#5f4100","secondary":"#7d5800",
          "on-tertiary-container":"#f7976d","on-error":"#ffffff","on-tertiary-fixed":"#360f00",
          "on-tertiary":"#ffffff","outline":"#89726e","on-primary-container":"#ff9183",
          "error-container":"#ffdad6","inverse-surface":"#313030","on-primary-fixed-variant":"#80281f",
          "primary":"#5c0d09","on-background":"#1b1b1b","surface-bright":"#fcf9f8",
          "on-secondary-container":"#755100","surface-container-lowest":"#ffffff",
          "inverse-on-surface":"#f3f0ef","tertiary-container":"#712f0c","on-tertiary-fixed-variant":"#76320f",
          "surface-variant":"#e5e2e1","tertiary":"#521b00","tertiary-fixed":"#ffdbcd",
          "secondary-fixed":"#ffdea9","surface-container-highest":"#e5e2e1","on-surface":"#1b1b1b",
          "on-surface-variant":"#56423f","tertiary-fixed-dim":"#ffb596","primary-fixed":"#ffdad5",
          "on-primary":"#ffffff","surface":"#fcf9f8","on-error-container":"#93000a"
        },
        borderRadius: { DEFAULT:"0.125rem", lg:"0.25rem", xl:"0.5rem", full:"0.75rem" },
        spacing: { gutter:"24px", base:"8px", "margin-mobile":"16px", "section-gap":"80px", "container-max":"1200px" },
        fontFamily: {
          "headline-lg":["EB Garamond"], "display-lg":["EB Garamond"], "body-md":["Manrope"],
          "headline-md":["EB Garamond"], "display-lg-mobile":["EB Garamond"], "body-lg":["Manrope"], "label-md":["Manrope"]
        },
        fontSize: {
          "headline-lg":["48px",{lineHeight:"1.2",fontWeight:"500"}],
          "display-lg":["64px",{lineHeight:"1.1",letterSpacing:"-0.02em",fontWeight:"600"}],
          "body-md":["16px",{lineHeight:"1.6",fontWeight:"400"}],
          "headline-md":["32px",{lineHeight:"1.3",fontWeight:"500"}],
          "display-lg-mobile":["40px",{lineHeight:"1.2",fontWeight:"600"}],
          "body-lg":["18px",{lineHeight:"1.6",fontWeight:"400"}],
          "label-md":["14px",{lineHeight:"1.2",letterSpacing:"0.05em",fontWeight:"600"}]
        }
      }
    }
  }
</script>
</head>
