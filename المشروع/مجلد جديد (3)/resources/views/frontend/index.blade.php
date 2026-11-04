@extends('frontend.layouts.app')

@section('meta_title', 'وصال | منصة زواج جاد للعرب والسوريين حول العالم')
@section('meta_description', 'وصال منصة عربية للزواج الجاد تجمع السوريين والعرب داخل بلدانهم وفي دول الاغتراب حول العالم، بهدف التعارف الهادف والزواج وتكوين أسرة مع مراعاة الخصوصية والتوافق.')

@section('content')
@php
    $hasAndroidLink = !empty($playStoreUrl);
    $hasIosLink = !empty($appStoreUrl);
    $hasAnyAppLink = !empty($appDownloadUrl);
@endphp

<style>
    :root {
        --ws-primary: #0F4C5C;
        --ws-primary-2: #1F6675;
        --ws-coral: #E07A5F;
        --ws-coral-soft: #F2B5A0;
        --ws-cream: #FDFBF7;
        --ws-white: #FFFFFF;
        --ws-ink: #1D2D44;
        --ws-muted: #6C7A89;
        --ws-line: #E8E4DE;
        --ws-shadow: 0 18px 55px rgba(29, 45, 68, .09);
    }

    html { scroll-behavior: smooth; }
    body.wesal-landing-body,
    body.wesal-landing-body .aiz-main-wrapper {
        background: var(--ws-cream) !important;
        color: var(--ws-ink) !important;
        font-family: 'Cairo', sans-serif !important;
    }
    body.wesal-landing-body { overflow-x: hidden; }
    .ws-landing, .ws-landing * { box-sizing: border-box; }
    .ws-landing { direction: rtl; color: var(--ws-ink); overflow: hidden; }
    .ws-container { width: min(1180px, calc(100% - 44px)); margin-inline: auto; }
    .ws-section { padding: clamp(58px, 7vw, 92px) 0; }
    .ws-section-soft { background: #F6F4EF; }

    .ws-header {
        position: sticky; top: 0; z-index: 100;
        background: rgba(253,251,247,.92);
        border-bottom: 1px solid rgba(15,76,92,.08);
        backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px);
    }
    .ws-header-inner { min-height: 78px; display: flex; align-items: center; justify-content: space-between; gap: 24px; }
    .ws-wordmark { color: var(--ws-primary); text-decoration: none !important; font-size: 27px; line-height: 1; font-weight: 900; letter-spacing: -.8px; white-space: nowrap; }
    .ws-wordmark span { display: block; color: var(--ws-muted); font-size: 10px; font-weight: 700; letter-spacing: 1.6px; margin-top: 8px; }
    .ws-nav { display: flex; align-items: center; gap: clamp(16px, 2.5vw, 32px); }
    .ws-nav a { color: var(--ws-ink); font-size: 13px; font-weight: 800; text-decoration: none !important; transition: color .2s ease; }
    .ws-nav a:hover { color: var(--ws-coral); }
    .ws-header-actions { display: flex; align-items: center; gap: 10px; white-space: nowrap; }
    .ws-login-link { color: var(--ws-primary); font-weight: 800; font-size: 13px; text-decoration: none !important; padding: 10px 12px; }
    .ws-header-cta, .ws-button-primary {
        display: inline-flex; align-items: center; justify-content: center; gap: 9px;
        border: 0; border-radius: 999px; padding: 12px 21px;
        background: var(--ws-primary); color: #fff !important; text-decoration: none !important;
        font-size: 13px; font-weight: 800; box-shadow: 0 9px 22px rgba(15,76,92,.17);
        transition: transform .2s ease, background .2s ease, box-shadow .2s ease;
    }
    .ws-header-cta:hover, .ws-button-primary:hover { background: var(--ws-primary-2); color: #fff !important; transform: translateY(-2px); box-shadow: 0 13px 28px rgba(15,76,92,.23); }
    .ws-mobile-toggle { display: none; border: 1px solid var(--ws-line); color: var(--ws-primary); background: white; border-radius: 13px; width: 44px; height: 44px; font-size: 23px; }
    .ws-mobile-menu { display: none; padding: 6px 0 18px; }
    .ws-mobile-menu a { display: block; padding: 12px 4px; border-top: 1px solid var(--ws-line); color: var(--ws-ink); font-weight: 800; text-decoration: none; }

    .ws-hero { position: relative; padding: clamp(54px, 8vw, 110px) 0 74px; isolation: isolate; }
    .ws-hero::before { content: ''; position: absolute; z-index: -1; width: 630px; height: 630px; border-radius: 50%; background: radial-gradient(circle, rgba(242,181,160,.25), rgba(242,181,160,0) 68%); top: -270px; left: -210px; }
    .ws-hero-grid { display: grid; grid-template-columns: minmax(0,1.04fr) minmax(340px,.96fr); align-items: center; gap: clamp(30px, 6vw, 78px); }
    .ws-eyebrow { display: inline-flex; align-items: center; gap: 8px; padding: 8px 13px; border-radius: 999px; background: rgba(31,102,117,.09); color: var(--ws-primary); font-size: 12px; font-weight: 900; margin-bottom: 22px; }
    .ws-eyebrow i { color: var(--ws-coral); font-size: 17px; }
    .ws-hero h1 { margin: 0; color: var(--ws-ink); font-size: clamp(36px, 5.3vw, 66px); line-height: 1.28; letter-spacing: -1.8px; font-weight: 900; }
    .ws-hero h1 em { color: var(--ws-coral); font-style: normal; }
    .ws-hero-copy { margin: 22px 0 0; max-width: 590px; color: var(--ws-muted); font-size: clamp(15px,1.35vw,18px); font-weight: 600; line-height: 2.05; }
    .ws-hero-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; margin-top: 30px; }
    .ws-button-outline { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 11px 19px; border: 1px solid rgba(15,76,92,.2); border-radius: 999px; color: var(--ws-primary) !important; background: rgba(255,255,255,.7); text-decoration: none !important; font-weight: 800; font-size: 13px; }
    .ws-hero-note { color: var(--ws-muted); font-size: 12px; font-weight: 700; margin-top: 16px; }
    .ws-hero-note i { color: var(--ws-coral); margin-left: 5px; }

    .ws-hero-art { min-height: 440px; position: relative; display: flex; align-items: center; justify-content: center; }
    .ws-art-orbit { position: absolute; width: min(420px, 94%); aspect-ratio: 1; border-radius: 50%; border: 1px solid rgba(31,102,117,.14); }
    .ws-art-orbit::before, .ws-art-orbit::after { content: ''; position: absolute; border-radius: 50%; border: 1px dashed rgba(224,122,95,.25); inset: 28px; }
    .ws-art-orbit::after { inset: 62px; border-style: solid; border-color: rgba(31,102,117,.1); }
    .ws-phone { position: relative; z-index: 2; width: 252px; min-height: 410px; padding: 14px; border: 7px solid #163B48; border-radius: 38px; background: #fff; box-shadow: 0 35px 80px rgba(15,76,92,.22); transform: rotate(-5deg); }
    .ws-phone-notch { width: 82px; height: 16px; margin: -5px auto 18px; border-radius: 0 0 13px 13px; background: #163B48; }
    .ws-phone-top { display: flex; align-items: center; justify-content: space-between; color: var(--ws-primary); font-size: 11px; font-weight: 900; }
    .ws-phone-status { display: flex; gap: 4px; }
    .ws-phone-status i { width: 5px; height: 5px; border-radius: 50%; background: var(--ws-coral); }
    .ws-phone-heading { font-weight: 900; font-size: 18px; color: var(--ws-ink); margin: 24px 0 5px; }
    .ws-phone-caption { color: var(--ws-muted); font-weight: 600; font-size: 10px; margin-bottom: 15px; }
    .ws-phone-profile { display: flex; align-items: center; gap: 10px; border: 1px solid #F0ECE6; border-radius: 15px; padding: 9px; margin-bottom: 9px; background: #FFFEFC; }
    .ws-phone-avatar { width: 46px; height: 46px; border-radius: 13px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 22px; background: linear-gradient(145deg,var(--ws-primary-2),#8AB4B8); }
    .ws-phone-avatar.coral { background: linear-gradient(145deg,var(--ws-coral),var(--ws-coral-soft)); }
    .ws-phone-lines { flex: 1; min-width: 0; }
    .ws-phone-lines b, .ws-phone-lines span { display: block; height: 7px; border-radius: 10px; background: #E8ECE9; }
    .ws-phone-lines b { width: 64%; margin-bottom: 7px; background: #B9D0D0; }
    .ws-phone-lines span { width: 87%; height: 5px; }
    .ws-phone-pill { height: 28px; border-radius: 999px; background: rgba(224,122,95,.13); color: var(--ws-coral); font-size: 9px; display: flex; align-items: center; justify-content: center; font-weight: 900; margin-top: 14px; }
    .ws-float-card { position: absolute; z-index: 3; display: flex; align-items: center; gap: 9px; padding: 12px 15px; border-radius: 15px; background: rgba(255,255,255,.95); border: 1px solid rgba(255,255,255,.9); box-shadow: var(--ws-shadow); color: var(--ws-primary); font-size: 12px; font-weight: 900; }
    .ws-float-card i { width: 30px; height: 30px; border-radius: 10px; display: flex; align-items: center; justify-content: center; background: rgba(224,122,95,.12); color: var(--ws-coral); font-size: 18px; }
    .ws-float-one { top: 60px; right: 2%; }
    .ws-float-two { bottom: 65px; left: 0; }
    .ws-hero-spark { position: absolute; color: var(--ws-coral); font-size: 27px; }
    .ws-spark-one { top: 92px; left: 17%; }
    .ws-spark-two { bottom: 74px; right: 8%; font-size: 19px; }

    .ws-proof-strip { border-block: 1px solid var(--ws-line); background: rgba(255,255,255,.64); }
    .ws-proof-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 20px; padding: 23px 0; }
    .ws-proof-item { display: flex; align-items: center; justify-content: center; gap: 11px; color: var(--ws-ink); font-size: 13px; font-weight: 800; text-align: center; }
    .ws-proof-item i { font-size: 23px; color: var(--ws-coral); }

    .ws-section-heading { max-width: 690px; margin: 0 auto 38px; text-align: center; }
    .ws-kicker { color: var(--ws-coral); font-size: 12px; font-weight: 900; letter-spacing: .4px; margin-bottom: 10px; }
    .ws-section-heading h2 { color: var(--ws-ink); font-size: clamp(25px,3.5vw,38px); line-height: 1.45; font-weight: 900; margin: 0 0 11px; }
    .ws-section-heading p { margin: 0; color: var(--ws-muted); font-size: 14px; line-height: 1.9; font-weight: 600; }
    .ws-benefits-grid { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: 18px; }
    .ws-benefit { background: #fff; border: 1px solid var(--ws-line); border-radius: 22px; padding: 25px 24px; box-shadow: 0 9px 30px rgba(29,45,68,.035); }
    .ws-benefit-icon { width: 52px; height: 52px; border-radius: 17px; background: rgba(31,102,117,.1); color: var(--ws-primary); display: flex; align-items: center; justify-content: center; font-size: 25px; margin-bottom: 18px; }
    .ws-benefit:nth-child(2) .ws-benefit-icon { background: rgba(224,122,95,.13); color: var(--ws-coral); }
    .ws-benefit:nth-child(3) .ws-benefit-icon { background: rgba(31,102,117,.08); color: var(--ws-primary-2); }
    .ws-benefit h3 { color: var(--ws-ink); margin: 0 0 9px; font-size: 17px; font-weight: 900; }
    .ws-benefit p { color: var(--ws-muted); margin: 0; font-size: 13px; line-height: 1.9; font-weight: 600; }

    .ws-members-section { background: #fff; }
    .ws-members-section.ws-alt { background: #F6F4EF; }
    .ws-members-head { display: flex; justify-content: space-between; align-items: end; gap: 18px; margin-bottom: 28px; }
    .ws-members-head .ws-kicker { margin-bottom: 6px; }
    .ws-members-head h2 { margin: 0 0 6px; color: var(--ws-ink); font-size: clamp(23px,3vw,32px); line-height: 1.4; font-weight: 900; }
    .ws-members-head p { margin: 0; color: var(--ws-muted); font-size: 13px; font-weight: 600; }
    .ws-members-count { flex-shrink: 0; padding: 8px 12px; border-radius: 999px; background: rgba(15,76,92,.08); color: var(--ws-primary); font-size: 11px; font-weight: 900; }
    .ws-members-grid { display: grid; grid-template-columns: repeat(5,minmax(0,1fr)); gap: 17px; }
    .ws-member-card { overflow: hidden; min-width: 0; background: #fff; border: 1px solid rgba(29,45,68,.08); border-radius: 19px; box-shadow: 0 7px 24px rgba(29,45,68,.055); transition: transform .22s ease, box-shadow .22s ease; }
    .ws-member-card:hover { transform: translateY(-5px); box-shadow: 0 16px 34px rgba(29,45,68,.11); }
    .ws-member-photo-wrap { position: relative; overflow: hidden; aspect-ratio: 4 / 4.65; background: #E8EFED; }
    .ws-member-photo { display: block; width: 100%; height: 100%; object-fit: cover; object-position: center 30%; }
    .ws-member-photo-shade { position: absolute; inset: 48% 0 0; background: linear-gradient(to bottom,transparent,rgba(15,35,42,.22)); pointer-events: none; }
    .ws-member-featured { position: absolute; top: 10px; right: 10px; z-index: 2; display: inline-flex; align-items: center; gap: 5px; background: var(--ws-coral); color: #fff; border-radius: 999px; padding: 6px 9px; font-size: 10px; font-weight: 900; box-shadow: 0 5px 13px rgba(224,122,95,.23); }
    .ws-member-details { padding: 13px 13px 14px; }
    .ws-member-name { margin: 0; color: var(--ws-ink); font-size: 15px; font-weight: 900; line-height: 1.5; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .ws-member-subtitle { margin: 3px 0 10px; color: var(--ws-muted); font-size: 10px; font-weight: 700; }
    .ws-package-name { display: flex; align-items: center; gap: 5px; min-width: 0; margin: 4px 0 10px; color: var(--ws-primary); font-size: 11px; font-weight: 900; }
    .ws-package-name i { color: var(--ws-coral); flex-shrink: 0; font-size: 15px; }
    .ws-package-name span { overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
    .ws-contact-cta { display: flex; align-items: center; justify-content: center; gap: 6px; width: 100%; min-height: 36px; padding: 7px 8px; border-radius: 11px; background: rgba(15,76,92,.08); color: var(--ws-primary) !important; text-decoration: none !important; font-size: 11px; font-weight: 900; transition: background .2s ease,color .2s ease; }
    .ws-contact-cta:hover { background: var(--ws-primary); color: #fff !important; }
    .ws-empty-state { border: 1px dashed rgba(15,76,92,.2); background: rgba(255,255,255,.64); color: var(--ws-muted); border-radius: 18px; text-align: center; padding: 34px 18px; font-size: 13px; font-weight: 700; }
    .ws-empty-state i { display: block; color: var(--ws-coral); font-size: 34px; margin-bottom: 9px; }

    .ws-steps-grid { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: 20px; counter-reset: ws-step; }
    .ws-step { position: relative; padding: 27px 25px; border-radius: 21px; background: #fff; border: 1px solid var(--ws-line); }
    .ws-step-num { display: inline-flex; width: 38px; height: 38px; border-radius: 13px; align-items: center; justify-content: center; background: var(--ws-primary); color: #fff; font-weight: 900; font-size: 14px; margin-bottom: 17px; }
    .ws-step i { float: left; color: var(--ws-coral); font-size: 27px; }
    .ws-step h3 { color: var(--ws-ink); font-size: 16px; font-weight: 900; margin: 0 0 8px; }
    .ws-step p { color: var(--ws-muted); margin: 0; font-size: 13px; line-height: 1.9; font-weight: 600; }

    .ws-download { position: relative; overflow: hidden; background: linear-gradient(125deg,#0F4C5C 0%,#1F6675 100%); color: #fff; }
    .ws-download::before { content: ''; position: absolute; width: 460px; height: 460px; border-radius: 50%; border: 1px solid rgba(255,255,255,.11); top: -270px; left: -100px; box-shadow: 0 0 0 34px rgba(255,255,255,.025),0 0 0 70px rgba(255,255,255,.02); }
    .ws-download-inner { position: relative; display: flex; justify-content: space-between; align-items: center; gap: 30px; }
    .ws-download h2 { color: #fff; font-size: clamp(25px,3.5vw,38px); font-weight: 900; margin: 0 0 12px; }
    .ws-download p { color: rgba(255,255,255,.78); font-size: 14px; line-height: 1.9; max-width: 620px; margin: 0; font-weight: 600; }
    .ws-store-buttons { display: flex; flex-wrap: wrap; gap: 11px; flex-shrink: 0; }
    .ws-store-button { min-width: 170px; min-height: 56px; display: flex; align-items: center; justify-content: center; gap: 10px; padding: 10px 17px; border-radius: 15px; background: #fff; color: var(--ws-primary) !important; text-decoration: none !important; font-size: 13px; font-weight: 900; }
    .ws-store-button i { font-size: 24px; }
    .ws-store-button small { display: block; font-size: 9px; color: var(--ws-muted); font-weight: 700; }
    .ws-store-button.is-soon { opacity: .72; cursor: default; }

    .ws-footer { background: #103E4A; color: rgba(255,255,255,.78); padding: 29px 0; }
    .ws-footer-inner { display: flex; align-items: center; justify-content: space-between; gap: 18px; }
    .ws-footer-brand { color: #fff; font-size: 20px; font-weight: 900; }
    .ws-footer-copy { font-size: 11px; font-weight: 600; margin-top: 5px; }
    .ws-footer-links { display: flex; flex-wrap: wrap; gap: 18px; }
    .ws-footer-links a { color: rgba(255,255,255,.85); text-decoration: none; font-size: 11px; font-weight: 800; }
    .ws-footer-links a:hover { color: var(--ws-coral-soft); }

    @media (max-width: 1050px) {
        .ws-nav { gap: 15px; }
        .ws-nav a { font-size: 12px; }
        .ws-members-grid { grid-template-columns: repeat(4,minmax(0,1fr)); }
        .ws-hero-grid { gap: 24px; }
    }
    @media (max-width: 820px) {
        .ws-container { width: min(100% - 34px, 620px); }
        .ws-header-inner { min-height: 68px; }
        .ws-nav, .ws-header-actions { display: none; }
        .ws-mobile-toggle { display: inline-flex; align-items: center; justify-content: center; }
        .ws-mobile-menu.is-open { display: block; }
        .ws-hero { padding: 48px 0 56px; }
        .ws-hero-grid { grid-template-columns: 1fr; }
        .ws-hero-copy { max-width: 650px; }
        .ws-hero-art { min-height: 390px; margin-top: 8px; }
        .ws-phone { transform: rotate(-3deg) scale(.9); }
        .ws-benefits-grid { grid-template-columns: 1fr; }
        .ws-benefit { display: grid; grid-template-columns: 50px 1fr; column-gap: 15px; padding: 19px; }
        .ws-benefit-icon { grid-row: span 2; margin-bottom: 0; }
        .ws-benefit h3 { align-self: end; }
        .ws-benefit p { align-self: start; }
        .ws-members-grid { grid-template-columns: repeat(3,minmax(0,1fr)); gap: 13px; }
        .ws-members-head { align-items: start; }
        .ws-members-head .ws-members-count { margin-top: 7px; }
        .ws-steps-grid { grid-template-columns: 1fr; gap: 12px; }
        .ws-download-inner { display: block; }
        .ws-store-buttons { margin-top: 24px; }
        .ws-footer-inner { align-items: flex-start; flex-direction: column; }
    }
    @media (max-width: 560px) {
        .ws-container { width: calc(100% - 30px); }
        .ws-wordmark { font-size: 24px; }
        .ws-hero h1 { font-size: clamp(34px,10vw,45px); letter-spacing: -1px; }
        .ws-hero-copy { font-size: 14px; line-height: 1.95; }
        .ws-hero-actions { display: grid; grid-template-columns: 1fr; }
        .ws-hero-actions a { width: 100%; }
        .ws-hero-art { min-height: 345px; margin-inline: -10px; }
        .ws-art-orbit { width: 310px; }
        .ws-phone { width: 218px; min-height: 360px; padding: 11px; border-width: 6px; border-radius: 32px; }
        .ws-phone-heading { margin-top: 18px; }
        .ws-phone-avatar { width: 40px; height: 40px; }
        .ws-float-card { padding: 9px 10px; font-size: 10px; }
        .ws-float-one { top: 20px; right: 0; }
        .ws-float-two { bottom: 25px; left: 0; }
        .ws-proof-grid { grid-template-columns: 1fr; gap: 12px; padding: 18px 0; }
        .ws-proof-item { justify-content: flex-start; text-align: right; font-size: 12px; }
        .ws-section { padding: 54px 0; }
        .ws-section-heading { margin-bottom: 27px; }
        .ws-members-head { display: block; }
        .ws-members-count { display: inline-flex; margin-top: 13px !important; }
        .ws-members-grid { grid-template-columns: repeat(2,minmax(0,1fr)); gap: 11px; }
        .ws-member-photo-wrap { aspect-ratio: 4 / 4.85; }
        .ws-member-details { padding: 10px 10px 11px; }
        .ws-member-name { font-size: 14px; }
        .ws-package-name { font-size: 10px; }
        .ws-member-featured { top: 7px; right: 7px; padding: 5px 7px; font-size: 9px; }
        .ws-contact-cta { min-height: 35px; font-size: 10.5px; }
        .ws-store-buttons { display: grid; grid-template-columns: 1fr; }
        .ws-store-button { width: 100%; }
        .ws-footer-links { gap: 12px; }
    }
    @media (max-width: 350px) {
        .ws-container { width: calc(100% - 24px); }
        .ws-members-grid { gap: 8px; }
        .ws-member-details { padding: 8px; }
        .ws-member-name { font-size: 13px; }
        .ws-contact-cta { font-size: 10px; }
    }
    @media (prefers-reduced-motion: reduce) {
        html { scroll-behavior: auto; }
        *, *::before, *::after { transition-duration: .01ms !important; animation-duration: .01ms !important; }
    }
</style>

<div class="ws-landing" id="top">
    <header class="ws-header">
        <div class="ws-container">
            <div class="ws-header-inner">
                {{-- اسم نصي مؤقت فقط؛ لا نضع شعاراً قبل استلام شعار وصال الرسمي. --}}
                <a class="ws-wordmark" href="#top" aria-label="وصال - الرئيسية">وصال<span>منصة زواج جاد للعرب حول العالم</span></a>
                <nav class="ws-nav" aria-label="التنقل الرئيسي">
                    <a href="#about">عن وصال</a>
                    <a href="#featured">الأعضاء المميزون</a>
                    <a href="#women">العضوات</a>
                    <a href="#men">الأعضاء</a>
                    <a href="#how-it-works">كيف يعمل</a>
                </nav>
                <div class="ws-header-actions">
                    @guest
                        <a class="ws-login-link" href="{{ route('login') }}">تسجيل الدخول</a>
                        <a class="ws-header-cta" href="{{ route('register') }}">إنشاء حساب <i class="las la-arrow-left"></i></a>
                    @endguest
                </div>
                <button class="ws-mobile-toggle" type="button" id="wsMobileToggle" aria-label="فتح القائمة" aria-expanded="false" aria-controls="wsMobileMenu"><i class="las la-bars"></i></button>
            </div>
            <nav class="ws-mobile-menu" id="wsMobileMenu" aria-label="قائمة الهاتف">
                <a href="#about">عن وصال</a>
                <a href="#featured">الأعضاء المميزون</a>
                <a href="#women">العضوات</a>
                <a href="#men">الأعضاء</a>
                <a href="#how-it-works">كيف يعمل</a>
                @guest
                    <a href="{{ route('login') }}">تسجيل الدخول</a>
                    <a href="{{ route('register') }}">إنشاء حساب جديد</a>
                @endguest
            </nav>
        </div>
    </header>

    <main>
        <section class="ws-hero" aria-labelledby="wsHeroTitle">
            <div class="ws-container ws-hero-grid">
                <div class="ws-hero-content">
                    <div class="ws-eyebrow"><i class="las la-heart"></i> زواج جاد للعرب والسوريين حول العالم</div>
                    <h1 id="wsHeroTitle">حين تبحث عن شريك حياة، ابدأ من <em>توافق جاد</em></h1>
                    <p class="ws-hero-copy">وصال منصة عربية مخصصة للتعارف الهادف بهدف الزواج وتكوين أسرة، تجمع السوريين والعرب داخل بلدانهم وفي مجتمعات الاغتراب حول العالم. تعرّف إلى ملفات شخصية واضحة، وحدد مواصفات شريك الحياة الذي تبحث عنه.</p>
                    <div class="ws-hero-actions">
                        <a class="ws-button-primary" href="#download"><i class="las la-mobile-alt"></i> تعرّف على التطبيق</a>
                        <a class="ws-button-outline" href="#featured"><i class="las la-users"></i> اكتشف الأعضاء</a>
                    </div>
                    <div class="ws-hero-note"><i class="las la-shield-alt"></i> الصور المعروضة تخضع لإعدادات الخصوصية والموافقة المعتمدة في وصال.</div>
                </div>

                <div class="ws-hero-art" aria-hidden="true">
                    <div class="ws-art-orbit"></div>
                    <div class="ws-float-card ws-float-one"><i class="las la-heart"></i><span>نية واضحة نحو الزواج</span></div>
                    <div class="ws-float-card ws-float-two"><i class="las la-user-check"></i><span>عرب وسوريون حول العالم</span></div>
                    <span class="ws-hero-spark ws-spark-one">✦</span><span class="ws-hero-spark ws-spark-two">✧</span>
                    <div class="ws-phone">
                        <div class="ws-phone-notch"></div>
                        <div class="ws-phone-top"><span>وصال</span><span class="ws-phone-status"><i></i><i></i><i></i></span></div>
                        <div class="ws-phone-heading">ابحث عن شريك الحياة المناسب</div>
                        <div class="ws-phone-caption">توافق واحترام وخصوصية</div>
                        <div class="ws-phone-profile"><div class="ws-phone-avatar"><i class="las la-user"></i></div><div class="ws-phone-lines"><b></b><span></span></div></div>
                        <div class="ws-phone-profile"><div class="ws-phone-avatar coral"><i class="las la-user"></i></div><div class="ws-phone-lines"><b></b><span></span></div></div>
                        <div class="ws-phone-profile"><div class="ws-phone-avatar"><i class="las la-user"></i></div><div class="ws-phone-lines"><b></b><span></span></div></div>
                        <div class="ws-phone-pill"><i class="las la-heart ml-1"></i> ابدأ رحلة زواج جاد</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="ws-proof-strip" aria-label="مميزات وصال">
            <div class="ws-container ws-proof-grid">
                <div class="ws-proof-item"><i class="las la-user-friends"></i><span>للعرب والسوريين حول العالم</span></div>
                <div class="ws-proof-item"><i class="las la-sliders-h"></i><span>توافق وفق معايير واضحة</span></div>
                <div class="ws-proof-item"><i class="las la-comments"></i><span>تواصل محترم داخل التطبيق</span></div>
            </div>
        </section>

        <section class="ws-section" id="about">
            <div class="ws-container">
                <div class="ws-section-heading">
                    <div class="ws-kicker">عن وصال</div>
                    <h2>منصة للزواج الجاد لا للمواعدة العابرة</h2>
                    <p>وصال مساحة عربية تجمع السوريين والعرب المقيمين في بلدانهم أو في الخارج، وتساعد الباحثين عن الزواج على التعارف الهادف وبناء أسرة على أساس من التوافق والاحترام.</p>
                </div>
                <div class="ws-benefits-grid">
                    <article class="ws-benefit"><div class="ws-benefit-icon"><i class="las la-id-card"></i></div><h3>زواج جاد لا مواعدة</h3><p>وصال مخصص للتعارف الجاد بهدف الزواج وتكوين أسرة، وليس للعلاقات العابرة أو التسلية.</p></article>
                    <article class="ws-benefit"><div class="ws-benefit-icon"><i class="las la-globe"></i></div><h3>مجتمع عربي حول العالم</h3><p>يجمع السوريين والعرب داخل بلدانهم وفي دول الاغتراب، لتبقى فرص التعارف الجاد ممكنة رغم المسافات.</p></article>
                    <article class="ws-benefit"><div class="ws-benefit-icon"><i class="las la-sliders-h"></i></div><h3>توافق وفق معايير تهمك</h3><p>استكشف التفضيلات بحسب بلد الإقامة والعمر والتعليم والمهنة والحالة الاجتماعية.</p></article>
                    <article class="ws-benefit"><div class="ws-benefit-icon"><i class="las la-shield-alt"></i></div><h3>خصوصيتك بيدك</h3><p>تحكم بالصور والمعلومات التي تظهر في ملفك؛ ولا تُعرض بيانات الهاتف أو البريد علناً دون موافقتك، مع خيارات الحظر والإبلاغ.</p></article>
                    <article class="ws-benefit"><div class="ws-benefit-icon"><i class="las la-comments"></i></div><h3>تواصل محترم داخل التطبيق</h3><p>إبداء الاهتمام وطلب التواصل ومتابعة الردود تتم داخل التطبيق بعد الاهتمام المتبادل، مع خيارات توثيق الحساب عبر الهاتف أو البريد الإلكتروني.</p></article>
                    <article class="ws-benefit"><div class="ws-benefit-icon"><i class="las la-gem"></i></div><h3>خيارات بحث وعضوية إضافية</h3><p>توفر العضوية المميزة مزايا إضافية مثل البحث المتقدم وأولوية الظهور وفق الباقة المتاحة.</p></article>
                </div>
            </div>
        </section>

        <section class="ws-section ws-members-section" id="featured">
            <div class="ws-container">
                <div class="ws-members-head">
                    <div><div class="ws-kicker">اختيارات وصال</div><h2>الأعضاء المميزون</h2><p>أحدث الأعضاء الذين لديهم باقة مدفوعة مفعّلة وصلاحية سارية، مع إظهار اسم الباقة المعتمد في وصال.</p></div>
                    <span class="ws-members-count">آخر 10 أعضاء مميزين</span>
                </div>
                @if ($featuredMembers->isNotEmpty())
                    <div class="ws-members-grid">
                        @foreach ($featuredMembers as $profile)
                            @include('frontend.inc.marketing_member_card', ['profile' => $profile, 'featured' => true, 'playStoreUrl' => $playStoreUrl, 'appStoreUrl' => $appStoreUrl])
                        @endforeach
                    </div>
                @else
                    <div class="ws-empty-state"><i class="las la-gem"></i>لا توجد حالياً ملفات تستوفي شروط العضوية المدفوعة السارية للعرض في هذا القسم.</div>
                @endif
            </div>
        </section>

        <section class="ws-section ws-members-section ws-alt" id="women">
            <div class="ws-container">
                <div class="ws-members-head">
                    <div><div class="ws-kicker">أحدث الملفات النسائية</div><h2>عضوات وصال</h2><p>أحدث العضوات اللواتي تم اعتماد ملفاتهن للظهور في وصال، مع احترام إعدادات الخصوصية.</p></div>
                    <span class="ws-members-count">آخر 10 عضوات سجّلن لدينا</span>
                </div>
                @if ($femaleMembers->isNotEmpty())
                    <div class="ws-members-grid">
                        @foreach ($femaleMembers as $profile)
                            @include('frontend.inc.marketing_member_card', ['profile' => $profile, 'featured' => false, 'playStoreUrl' => $playStoreUrl, 'appStoreUrl' => $appStoreUrl])
                        @endforeach
                    </div>
                @else
                    <div class="ws-empty-state"><i class="las la-user-friends"></i>ستظهر بطاقات العضوات هنا عند توفر ملفات منشورة.</div>
                @endif
            </div>
        </section>

        <section class="ws-section ws-members-section" id="men">
            <div class="ws-container">
                <div class="ws-members-head">
                    <div><div class="ws-kicker">أحدث الملفات الرجالية</div><h2>أعضاء وصال</h2><p>أحدث الأعضاء الذين تم اعتماد ملفاتهم للظهور في وصال، مع احترام إعدادات الخصوصية.</p></div>
                    <span class="ws-members-count">آخر 10 أعضاء سجّلوا لدينا</span>
                </div>
                @if ($maleMembers->isNotEmpty())
                    <div class="ws-members-grid">
                        @foreach ($maleMembers as $profile)
                            @include('frontend.inc.marketing_member_card', ['profile' => $profile, 'featured' => false, 'playStoreUrl' => $playStoreUrl, 'appStoreUrl' => $appStoreUrl])
                        @endforeach
                    </div>
                @else
                    <div class="ws-empty-state"><i class="las la-user-friends"></i>ستظهر بطاقات الأعضاء هنا عند توفر ملفات منشورة.</div>
                @endif
            </div>
        </section>

        <section class="ws-section ws-section-soft" id="how-it-works">
            <div class="ws-container">
                <div class="ws-section-heading"><div class="ws-kicker">خطوات نحو شريك الحياة</div><h2>كيف تبدأ رحلتك في وصال؟</h2><p>ابدأ بملف يعكس معلوماتك وتطلعاتك، ثم استكشف التوافق واطلب التواصل باحترام من داخل التطبيق.</p></div>
                <div class="ws-steps-grid">
                    <article class="ws-step"><span class="ws-step-num">01</span><i class="las la-user-edit"></i><h3>أنشئ ملفك</h3><p>أضف معلوماتك الأساسية وما تفضله في شريك الحياة.</p></article>
                    <article class="ws-step"><span class="ws-step-num">02</span><i class="las la-search"></i><h3>استكشف التوافق</h3><p>استخدم معايير البحث والتفضيلات لاكتشاف الملفات الأقرب لما تبحث عنه.</p></article>
                    <article class="ws-step"><span class="ws-step-num">03</span><i class="las la-comments"></i><h3>ابدأ التواصل</h3><p>بعد الاهتمام المتبادل، أرسل طلب التواصل وتابع الردود داخل التطبيق.</p></article>
                </div>
            </div>
        </section>

        <section class="ws-section ws-download" id="download">
            <div class="ws-container ws-download-inner">
                <div>
                    <div class="ws-kicker" style="color:#F2B5A0">ابدأ مع وصال</div>
                    <h2>ابدأ خطوتك نحو زواج جاد</h2>
                    @if ($hasAnyAppLink)
                        <p>اختر المتجر المناسب لجهازك، وأنشئ ملفك وتعرّف إلى العرب والسوريين الباحثين عن الزواج الجاد حول العالم. طلب التواصل يتم من داخل التطبيق.</p>
                    @else
                        <p>نعمل على تجهيز روابط التحميل الرسمية. سنفعّل أزرار المتاجر هنا فور نشر التطبيق وإضافة روابطه من إعدادات الموقع.</p>
                    @endif
                </div>
                <div class="ws-store-buttons">
                    @if ($hasAndroidLink)
                        <a class="ws-store-button" href="{{ $playStoreUrl }}" target="_blank" rel="noopener noreferrer"><i class="lab la-google-play"></i><span><small>حمّل التطبيق من</small>Google Play</span></a>
                    @else
                        <span class="ws-store-button is-soon"><i class="lab la-google-play"></i><span><small>قريباً</small>Google Play</span></span>
                    @endif
                    @if ($hasIosLink)
                        <a class="ws-store-button" href="{{ $appStoreUrl }}" target="_blank" rel="noopener noreferrer"><i class="lab la-apple"></i><span><small>حمّل التطبيق من</small>App Store</span></a>
                    @else
                        <span class="ws-store-button is-soon"><i class="lab la-apple"></i><span><small>لاحقاً</small>App Store</span></span>
                    @endif
                </div>
            </div>
        </section>
    </main>

    <footer class="ws-footer">
        <div class="ws-container ws-footer-inner">
            <div><div class="ws-footer-brand">وصال</div><div class="ws-footer-copy">منصة زواج جاد للعرب والسوريين حول العالم. © {{ date('Y') }} وصال. جميع الحقوق محفوظة.</div></div>
            <div class="ws-footer-links">
                <a href="{{ route('custom-pages.show_custom_page', ['slug' => 'terms-conditions']) }}">سياسة الخصوصية والشروط والأحكام</a>
                <a href="{{ route('contact_us') }}">تواصل معنا</a>
                <a href="#top">العودة للأعلى</a>
            </div>
        </div>
    </footer>
</div>

<script>
function wsOpenAppDownload(event, link) {
    var androidUrl = (link.getAttribute('data-android-url') || '').trim();
    var iosUrl = (link.getAttribute('data-ios-url') || '').trim();
    var agent = navigator.userAgent || '';
    var isApple = /iPhone|iPad|iPod/i.test(agent);
    var isAndroid = /Android/i.test(agent);
    var storeUrl = isApple ? iosUrl : (isAndroid ? androidUrl : '');
    if (!storeUrl && !isApple && !isAndroid && androidUrl && !iosUrl) storeUrl = androidUrl;
    if (!storeUrl && !isApple && !isAndroid && iosUrl && !androidUrl) storeUrl = iosUrl;
    if (!/^https?:\/\//i.test(storeUrl)) storeUrl = '';

    if (storeUrl) {
        event.preventDefault();
        window.open(storeUrl, '_blank', 'noopener,noreferrer');
        return false;
    }

    // قبل نشر التطبيق تبقى الوجهة هي قسم روابط التحميل مع توضيح أنه قريباً.
    return true;
}

(function () {
    var toggle = document.getElementById('wsMobileToggle');
    var menu = document.getElementById('wsMobileMenu');
    if (!toggle || !menu) return;

    toggle.addEventListener('click', function () {
        var isOpen = menu.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        toggle.innerHTML = isOpen ? '<i class="las la-times"></i>' : '<i class="las la-bars"></i>';
    });

    menu.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            menu.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.innerHTML = '<i class="las la-bars"></i>';
        });
    });
})();
</script>
@endsection
