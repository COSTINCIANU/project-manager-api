<?php
// =====================================================
// InvoiceService.php — Génération des factures PDF
// Crée une facture personnalisée avec logo et infos
// Utilise DomPDF pour la génération du PDF
// =====================================================

namespace App\Service;

use App\Entity\User;
use Dompdf\Dompdf;
use Dompdf\Options;

class InvoiceService
{
    // =====================
    // Génère un PDF de facture et retourne le contenu binaire
    // =====================
    public function genererFacture(
        User $user,
        string $plan,
        float $montant,
        string $numeroFacture,
        \DateTime $dateFacture
    ): string {
        // Configuration DomPDF
        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);

        // Nom du plan lisible
        $nomPlan = match($plan) {
            'pro' => 'Project Manager Pro',
            'enterprise' => 'Project Manager Entreprise',
            default => 'Project Manager Gratuit',
        };

        // Génère le HTML de la facture
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

    // =====================
    // Génère le HTML de la facture
    // =====================
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

        return "
<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 13px; color: #2c2c2c; background: #fff; }

        .facture { padding: 40px; max-width: 800px; margin: 0 auto; }

        /* En-tête */
        .entete { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; border-bottom: 3px solid #6366F1; padding-bottom: 20px; }
        .logo-zone { display: flex; flex-direction: column; }
        .logo-texte { font-size: 28px; font-weight: bold; color: #6366F1; letter-spacing: -1px; }
        .logo-sous-texte { font-size: 11px; color: #888; margin-top: 2px; }
        .facture-info { text-align: right; }
        .facture-titre { font-size: 22px; font-weight: bold; color: #2c2c2c; margin-bottom: 6px; }
        .facture-numero { font-size: 13px; color: #6366F1; font-weight: bold; }
        .facture-date { font-size: 12px; color: #888; margin-top: 4px; }

        /* Adresses */
        .adresses { display: flex; justify-content: space-between; margin-bottom: 35px; }
        .adresse-bloc { width: 48%; }
        .adresse-titre { font-size: 11px; font-weight: bold; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
        .adresse-nom { font-size: 15px; font-weight: bold; color: #2c2c2c; margin-bottom: 4px; }
        .adresse-detail { font-size: 12px; color: #555; line-height: 1.6; }

        /* Tableau */
        .tableau { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
        .tableau thead tr { background: #6366F1; color: #fff; }
        .tableau thead th { padding: 12px 15px; text-align: left; font-size: 12px; font-weight: bold; }
        .tableau tbody tr { border-bottom: 1px solid #f0f0f0; }
        .tableau tbody tr:nth-child(even) { background: #f9f9ff; }
        .tableau tbody td { padding: 14px 15px; font-size: 13px; }
        .tableau tfoot tr { border-top: 2px solid #6366F1; }
        .tableau tfoot td { padding: 10px 15px; font-size: 13px; }

        /* Totaux */
        .totaux { float: right; width: 260px; margin-bottom: 35px; }
        .ligne-total { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; border-bottom: 1px solid #f0f0f0; }
        .ligne-total.total-final { font-size: 16px; font-weight: bold; color: #6366F1; border-top: 2px solid #6366F1; border-bottom: none; padding-top: 10px; margin-top: 4px; }

        /* Badge plan */
        .badge-plan { display: inline-block; background: #6366F1; color: #fff; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; }

        /* Pied de page */
        .pied { clear: both; border-top: 1px solid #eee; padding-top: 20px; margin-top: 20px; }
        .pied-texte { font-size: 11px; color: #aaa; text-align: center; line-height: 1.8; }
        .pied-merci { font-size: 14px; font-weight: bold; color: #6366F1; text-align: center; margin-bottom: 10px; }

        /* Statut payé */
        .statut-paye { position: absolute; top: 40px; right: 40px; border: 3px solid #27AE60; color: #27AE60; padding: 6px 16px; border-radius: 6px; font-size: 16px; font-weight: bold; transform: rotate(-15deg); opacity: 0.8; }
    </style>
</head>
<body>
<div class='facture'>

    <!-- En-tête -->
    <div class='entete'>
        <div class='logo-zone'>
            <svg width='280' height='80' viewBox='0 0 280 80' xmlns='http://www.w3.org/2000/svg'>
                <polygon points='25,5 45,5 55,22 45,39 25,39 15,22' fill='#6366F1'/>
                <polygon points='27,9 43,9 51,22 43,35 27,35 19,22' fill='#4F46E5'/>
                <line x1='35' y1='9' x2='35' y2='35' stroke='#818CF8' stroke-width='1.2'/>
                <line x1='19' y1='22' x2='51' y2='22' stroke='#818CF8' stroke-width='1.2'/>
                <line x1='27' y1='9' x2='43' y2='35' stroke='#C7D2FE' stroke-width='0.7' opacity='0.6'/>
                <line x1='43' y1='9' x2='27' y2='35' stroke='#C7D2FE' stroke-width='0.7' opacity='0.6'/>
                <circle cx='35' cy='22' r='4' fill='#ffffff'/>
                <circle cx='35' cy='9' r='2' fill='#C7D2FE'/>
                <circle cx='35' cy='35' r='2' fill='#C7D2FE'/>
                <circle cx='19' cy='22' r='2' fill='#C7D2FE'/>
                <circle cx='51' cy='22' r='2' fill='#C7D2FE'/>
                <text x='62' y='26' font-family='Helvetica' font-size='18' font-weight='800' fill='#1e1b4b' letter-spacing='-0.5'>Project Manager</text>
                <text x='63' y='38' font-family='Helvetica' font-size='7' fill='#6366F1' letter-spacing='2'>PLATEFORME DE GESTION DE PROJETS</text>
                <rect x='62' y='42' width='160' height='1' fill='#6366F1' opacity='0.3' rx='0.5'/>
                </svg>
            <div style='margin-top: 12px; font-size: 11px; color: #888; line-height: 1.6;'>
                COSTINCIANU Gheorghina<br>
                contact@costincianu.fr<br>
                project-manager.costincianu.fr
            </div>
        </div>
        <div class='facture-info'>
            <div class='facture-titre'>FACTURE</div>
            <div class='facture-numero'>{$numeroFacture}</div>
            <div class='facture-date'>Date : {$dateStr}</div>
            <div class='facture-date'>Échéance : {$dateEcheance}</div>
            <div style='margin-top: 10px;'>
                <span class='badge-plan'>{$nomPlan}</span>
            </div>
        </div>
    </div>

    <!-- Adresses -->
    <div class='adresses'>
        <div class='adresse-bloc'>
            <div class='adresse-titre'>Émetteur</div>
            <div class='adresse-nom'>Project Manager</div>
            <div class='adresse-detail'>
                COSTINCIANU Gheorghina<br>
                contact@costincianu.fr<br>
                France
            </div>
        </div>
        <div class='adresse-bloc'>
            <div class='adresse-titre'>Facturé à</div>
            <div class='adresse-nom'>{$user->getName()}</div>
            <div class='adresse-detail'>
                {$user->getEmail()}<br>
                Abonnement {$nomPlan}
            </div>
        </div>
    </div>

    <!-- Tableau des services -->
    <table class='tableau'>
        <thead>
            <tr>
                <th>Description</th>
                <th>Période</th>
                <th>Quantité</th>
                <th style='text-align: right;'>Montant HT</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>{$nomPlan}</strong><br>
                    <span style='font-size: 11px; color: #888;'>Abonnement mensuel — accès complet à la plateforme</span>
                </td>
                <td style='color: #888;'>{$dateStr} — {$dateEcheance}</td>
                <td>1</td>
                <td style='text-align: right; font-weight: bold;'>{$montantHT} €</td>
            </tr>
        </tbody>
    </table>

    <!-- Totaux -->
    <div class='totaux'>
        <div class='ligne-total'>
            <span>Sous-total HT</span>
            <span>{$montantHT} €</span>
        </div>
        <div class='ligne-total'>
            <span>TVA (20%)</span>
            <span>{$tva} €</span>
        </div>
        <div class='ligne-total total-final'>
            <span>Total TTC</span>
            <span>{$montant} €</span>
        </div>
    </div>

    <!-- Pied de page -->
    <div class='pied'>
        <div style='text-align: center; margin-bottom: 8px;'>
            <svg width='140' height='30' viewBox='0 0 140 30' xmlns='http://www.w3.org/2000/svg'>
            <polygon points='10,2 20,2 25,11 20,20 10,20 5,11' fill='#6366F1'/>
            <polygon points='11,5 19,5 23,11 19,17 11,17 7,11' fill='#4F46E5'/>
            <line x1='15' y1='5' x2='15' y2='17' stroke='#818CF8' stroke-width='0.8'/>
            <line x1='7' y1='11' x2='23' y2='11' stroke='#818CF8' stroke-width='0.8'/>
            <circle cx='15' cy='11' r='2' fill='#ffffff'/>
            <text x='30' y='14' font-family='Helvetica' font-size='9' font-weight='800' fill='#1e1b4b'>Project Manager</text>
            <text x='30' y='22' font-family='Helvetica' font-size='5' fill='#6366F1' letter-spacing='1'>GESTION DE PROJETS SAAS</text>
            </svg>
            </div>
            <div class='pied-merci'>Merci pour votre confiance !</div>
        <div class='pied-texte'>
            Facture générée automatiquement par Project Manager<br>
            Pour toute question : contact@costincianu.fr<br>
            project-manager.costincianu.fr
        </div>
    </div>

</div>
</body>
</html>";
    }
}
