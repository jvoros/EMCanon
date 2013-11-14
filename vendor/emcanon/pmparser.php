<?
/*

A Class to parse PubMed provided XML data. 
Can use a Pubmed URL or PMID #.

@author: Jeremy Voros

*/

class PMParser
{
    
    static function getPMIDfromURL($url)
    {
        // will work with variously formatted urls, start with/without "http://", end with/without trailing /, or with just PMID input
        $pieces = explode('/', $url); // make array of url bits
        $id = preg_grep("/[0-9]/", $pieces); // get just the PMID portion of URL
        $id = array_values($id); // reset array keys
        if ($id[0]) {
            return $id[0]; // return the PMID
        } else {
            return "There is an error with the url";
        }
    }

    static function getArticle($pmid) 
    {    
        $url = "http://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=pubmed&retmode=xml&id=" . $pmid; // NCBI API to get XML summary by PMID
        
        $xml = simplexml_load_file($url);
        
        // parse the XML into bits we want
        $article = array(
            'ref_pmid'      => (string) $xml->PubmedArticle->MedlineCitation->PMID,
            'ref_year'      => (string) $xml->PubmedArticle->MedlineCitation->DateCreated->Year,
            'ref_month'     => (string) $xml->PubmedArticle->MedlineCitation->DateCreated->Month,
            'ref_day'       => (string) $xml->PubmedArticle->MedlineCitation->DateCreated->Day,
            'ref_journal'   => (string) $xml->PubmedArticle->MedlineCitation->Article->Journal->Title,
            'ref_title'     => (string) $xml->PubmedArticle->MedlineCitation->Article->ArticleTitle,
            'ref_fname'     => (string) $xml->PubmedArticle->MedlineCitation->Article->AuthorList->Author[0]->ForeName,
            'ref_lname'     => (string) $xml->PubmedArticle->MedlineCitation->Article->AuthorList->Author[0]->LastName,
            'ref_abstract'  => (string) $xml->PubmedArticle->MedlineCitation->Article->Abstract->AbstractText
        );
        
        return $article;
    }
    
    static function getArticleFromURL($url)
    {
        return self::getArticle(self::getPMIDfromURL($url));
    }
}