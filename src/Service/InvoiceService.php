<?php
// =====================================================
// InvoiceService.php — Génération des factures PDF
// Crée une facture personnalisée avec logo et infos
// Utilise DomPDF pour la génération du PDF
// Mise en page 100% avec des tables pour compatibilité DomPDF
// =====================================================

namespace App\Service;

use App\Entity\User;
use Dompdf\Dompdf;
use Dompdf\Options;

class InvoiceService
{
    public function genererFacture(
        User $user,
        string $plan,
        float $montant,
        string $numeroFacture,
        \DateTime $dateFacture
    ): string {
        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);

        $nomPlan = match($plan) {
            'pro' => 'Project Manager Pro',
            'enterprise' => 'Project Manager Entreprise',
            default => 'Project Manager Gratuit',
        };

        $html = $this->genererHtml(
            user: $user,
            nomPlan: $nomPlan,
            montant: $montant,
            numeroFacture: $numeroFacture,
            dateFacture: $dateFacture
        );

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function genererHtml(
        User $user,
        string $nomPlan,
        float $montant,
        string $numeroFacture,
        \DateTime $dateFacture
    ): string {
        $montantHT = round($montant / 1.2, 2);
        $tva = round($montant - $montantHT, 2);
        $dateStr = $dateFacture->format('d/m/Y');
        $dateEcheance = (clone $dateFacture)->modify('+30 days')->format('d/m/Y');
        $userName = $user->getName();
        $userEmail = $user->getEmail();

        return "
<!DOCTYPE html>
<html lang='fr'>
<head>
<meta charset='UTF-8'>
<style>
    body { font-family: Helvetica, Arial, sans-serif; font-size: 13px; color: #2c2c2c; margin: 0; padding: 0; }
    table { border-collapse: collapse; }
    .badge { background: #6366F1; color: #fff; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: bold; }
    .section-titre { font-size: 10px; font-weight: bold; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
    .total-ligne { padding: 5px 0; border-bottom: 1px solid #f0f0f0; }
    .total-final { font-size: 15px; font-weight: bold; color: #6366F1; border-top: 2px solid #6366F1; padding-top: 8px; margin-top: 4px; }
</style>
</head>
<body>
<table width='100%' cellpadding='0' cellspacing='0' style='padding: 40px;'>

    <!-- EN-TETE -->
    <tr>
        <td width='55%' valign='top'>
            <img src='https://api.costincianu.fr/images/logos/logo_project_manager_grand.png' style='height: 120px; width: auto;' />
            <span style='font-size: 11px; color: #888; line-height: 1.8;'>
                COSTINCIANU Gheorghina<br>
                contact@costincianu.fr<br>
                project-manager.costincianu.fr
            </span>
        </td>
        <td width='45%' valign='top' align='right'>
            <div style='font-size: 28px; font-weight: bold; color: #2c2c2c; margin-bottom: 8px;'>FACTURE</div>
            <div style='font-size: 14px; color: #6366F1; font-weight: bold; margin-bottom: 4px;'>{$numeroFacture}</div>
            <div style='font-size: 12px; color: #888; margin-bottom: 2px;'>Date : {$dateStr}</div>
            <div style='font-size: 12px; color: #888; margin-bottom: 10px;'>Échéance : {$dateEcheance}</div>
            <span class='badge'>{$nomPlan}</span>
        </td>
    </tr>

    <!-- LIGNE SEPARATRICE -->
    <tr>
        <td colspan='2' style='padding: 15px 0;'>
            <table width='100%'><tr><td style='border-top: 3px solid #6366F1;'></td></tr></table>
        </td>
    </tr>

    <!-- ADRESSES -->
    <tr>
        <td width='50%' valign='top' style='padding-bottom: 30px; padding-right: 20px;'>
            <div class='section-titre'>Émetteur</div>
            <div style='font-size: 15px; font-weight: bold; color: #2c2c2c; margin-bottom: 6px;'>Project Manager</div>
            <div style='font-size: 12px; color: #555; line-height: 1.7;'>
                COSTINCIANU Gheorghina<br>
                contact@costincianu.fr<br>
                France
            </div>
        </td>
        <td width='50%' valign='top' style='padding-bottom: 30px;'>
            <div class='section-titre'>Facturé à</div>
            <div style='font-size: 15px; font-weight: bold; color: #2c2c2c; margin-bottom: 6px;'>{$userName}</div>
            <div style='font-size: 12px; color: #555; line-height: 1.7;'>
                {$userEmail}<br>
                Plan {$nomPlan}
            </div>
        </td>
    </tr>

    <!-- TABLEAU DES SERVICES -->
    <tr>
        <td colspan='2' style='padding-bottom: 20px;'>
            <table width='100%' cellpadding='0' cellspacing='0'>
                <thead>
                    <tr style='background: #6366F1; color: #fff;'>
                        <th style='padding: 12px 15px; text-align: left; font-size: 12px;'>Description</th>
                        <th style='padding: 12px 15px; text-align: left; font-size: 12px;'>Période</th>
                        <th style='padding: 12px 15px; text-align: center; font-size: 12px;'>Qté</th>
                        <th style='padding: 12px 15px; text-align: right; font-size: 12px;'>Montant HT</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style='background: #f9f9ff;'>
                        <td style='padding: 14px 15px;'>
                            <strong>{$nomPlan}</strong><br>
                            <span style='font-size: 11px; color: #888;'>Abonnement mensuel — accès complet</span>
                        </td>
                        <td style='padding: 14px 15px; font-size: 12px; color: #888;'>{$dateStr} — {$dateEcheance}</td>
                        <td style='padding: 14px 15px; text-align: center;'>1</td>
                        <td style='padding: 14px 15px; text-align: right; font-weight: bold;'>{$montantHT} €</td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>

    <!-- TOTAUX -->
    <tr>
        <td width='55%'></td>
        <td width='45%' style='padding-bottom: 30px;'>
            <table width='100%' cellpadding='0' cellspacing='0'>
                <tr>
                    <td class='total-ligne' style='padding: 6px 0;'>Sous-total HT</td>
                    <td class='total-ligne' style='padding: 6px 0; text-align: right;'>{$montantHT} €</td>
                </tr>
                <tr>
                    <td class='total-ligne' style='padding: 6px 0;'>TVA (20%)</td>
                    <td class='total-ligne' style='padding: 6px 0; text-align: right;'>{$tva} €</td>
                </tr>
                <tr>
                    <td style='padding-top: 10px; font-size: 15px; font-weight: bold; color: #6366F1; border-top: 2px solid #6366F1;'>Total TTC</td>
                    <td style='padding-top: 10px; font-size: 15px; font-weight: bold; color: #6366F1; text-align: right; border-top: 2px solid #6366F1;'>{$montant} €</td>
                </tr>
            </table>
        </td>
    </tr>

    <!-- PIED DE PAGE -->
    <tr>
        <td colspan='2' style='border-top: 1px solid #eee; padding-top: 20px; text-align: center;'>
            <img src='https://api.costincianu.fr/images/logos/logo_project_manager_petit.png' style='height: 40px; width: auto;' />
            <div style='font-size: 14px; font-weight: bold; color: #6366F1; margin-bottom: 6px;'>Merci pour votre confiance !</div>
            <div style='font-size: 11px; color: #aaa; line-height: 1.8;'>
                Facture générée automatiquement par Project Manager<br>
                Pour toute question : contact@costincianu.fr<br>
                project-manager.costincianu.fr
            </div>
        </td>
    </tr>

</table>
</body>
</html>";
    }
}
