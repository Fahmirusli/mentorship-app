import json
import sys
import time
import os
import random
import logging
import argparse

# Suppress logs
os.environ['WDM_LOG_LEVEL'] = '0'
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3'

from bs4 import BeautifulSoup
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from webdriver_manager.chrome import ChromeDriverManager

# Configure logging to stderr so it doesn't corrupt stdout JSON
logging.basicConfig(stream=sys.stderr, level=logging.INFO, format='[%(levelname)s] %(message)s')

def get_driver():
    options = Options()
    options.add_argument('--headless=new')
    options.add_argument('--disable-gpu')
    options.add_argument('--no-sandbox')
    options.add_argument('--disable-dev-shm-usage')
    options.add_argument(f'--window-size={random.randint(1024, 1920)},{random.randint(768, 1080)}')
    options.add_argument('--disable-blink-features=AutomationControlled')
    options.add_argument('--disable-infobars')
    
    user_agents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/121.0'
    ]
    options.add_argument(f'user-agent={random.choice(user_agents)}')
    
    options.add_experimental_option("excludeSwitches", ["enable-automation"])
    options.add_experimental_option('useAutomationExtension', False)
    
    service = Service(ChromeDriverManager().install())
    driver = webdriver.Chrome(service=service, options=options)
    
    driver.execute_cdp_cmd("Page.addScriptToEvaluateOnNewDocument", {
        "source": """
            Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
            Object.defineProperty(navigator, 'plugins', { get: () => [1, 2, 3] });
            Object.defineProperty(navigator, 'languages', { get: () => ['en-US', 'en'] });
        """
    })
    
    return driver

def random_sleep(min_sec=2, max_sec=5):
    time.sleep(random.uniform(min_sec, max_sec))

# ============================================
# JOBSTREET SCRAPER
# ============================================
def scrape_jobstreet(driver, keyword):
    logging.info(f"Scraping JobStreet for '{keyword}'...")
    jobs = []
    try:
        url = f"https://www.jobstreet.com.my/jobs?keywords={keyword}"
        driver.get(url)
        random_sleep()
        
        try:
            WebDriverWait(driver, 10).until(
                EC.presence_of_element_located((By.TAG_NAME, 'article'))
            )
        except:
            logging.warning("JobStreet: No articles found (timeout).")

        soup = BeautifulSoup(driver.page_source, 'html.parser')
        
        articles = soup.find_all('article')
        if not articles:
            articles = soup.select('[data-automation="jobListing"]')

        logging.info(f"JobStreet: Found {len(articles)} potential jobs.")

        for article in articles[:20]:
            try:
                title_elem = article.find('a', attrs={'data-automation': 'jobTitle'}) or article.find('h1')
                if title_elem and title_elem.find('a'):
                    title_elem = title_elem.find('a')
                company_elem = article.find('a', attrs={'data-automation': 'jobCompany'})
                location_elem = article.find('a', attrs={'data-automation': 'jobLocation'})
                
                salary = 'Not specified'
                salary_elems = article.select('span')
                for span in salary_elems:
                    text = span.get_text(strip=True)
                    if 'MYR' in text or ('RM' in text and any(char.isdigit() for char in text)):
                        salary = text
                        break

                if title_elem:
                    href = title_elem.get('href', '')
                    ext_url = href if href.startswith('http') else "https://www.jobstreet.com.my" + href.split('?')[0]
                    jobs.append({
                        'title': title_elem.text.strip(),
                        'company': company_elem.text.strip() if company_elem else 'Confidential',
                        'location': location_elem.text.strip() if location_elem else 'Malaysia',
                        'source': 'jobstreet',
                        'external_url': ext_url,
                        'description': "See full details at JobStreet.",
                        'salary': salary,
                        'requirements': []
                    })
            except Exception as e:
                logging.warning(f"JobStreet info extraction error: {e}")
                continue
    except Exception as e:
        logging.error(f"JobStreet Error: {e}")
        
    return jobs

# ============================================
# MAUKERJA SCRAPER (Replaces Hiredly)
# ============================================
def scrape_maukerja(driver, keyword):
    logging.info(f"Scraping MauKerja for '{keyword}'...")
    jobs = []
    try:
        url = f"https://www.maukerja.my/en/search?q={keyword.replace(' ', '+')}"
        driver.get(url)
        random_sleep(3, 6)
        
        soup = BeautifulSoup(driver.page_source, 'html.parser')
        
        # MauKerja uses job listing cards
        cards = soup.select('.job-card') or soup.select('.card-job') or soup.select('[class*="job"]')
        
        # Fallback: find all links that look like job listings
        if not cards:
            cards = soup.select('a[href*="/en/job/"]') or soup.select('a[href*="/job/"]')
        
        logging.info(f"MauKerja: Found {len(cards)} potential job cards.")
        
        unique_urls = set()
        for card in cards:
            if len(jobs) >= 20:
                break
            
            try:
                # Get the link
                link = card if card.name == 'a' else card.find('a')
                if not link:
                    continue
                    
                href = link.get('href', '')
                if href in unique_urls or not href:
                    continue
                unique_urls.add(href)
                
                # Extract title
                title_tag = card.find('h2') or card.find('h3') or card.find('h4')
                if not title_tag:
                    title_tag = link
                
                title = title_tag.get_text(strip=True) if title_tag else ''
                if not title or len(title) < 3:
                    continue
                
                # Extract company and salary from text elements
                company = 'MauKerja Listed Company'
                salary = 'Not specified'
                location = 'Malaysia'
                
                text_elems = card.find_all(['p', 'span', 'div'])
                for elem in text_elems:
                    text = elem.get_text(strip=True)
                    if 'RM' in text and any(c.isdigit() for c in text):
                        salary = text
                    elif any(loc in text.lower() for loc in ['kuala lumpur', 'selangor', 'penang', 'johor', 'remote', 'malaysia']):
                        location = text
                    elif len(text) > 3 and company == 'MauKerja Listed Company' and text != title:
                        company = text
                
                ext_url = href if href.startswith('http') else "https://www.maukerja.my" + href
                
                jobs.append({
                    'title': title,
                    'company': company,
                    'location': location,
                    'source': 'maukerja',
                    'external_url': ext_url,
                    'description': 'Check MauKerja for details.',
                    'salary': salary,
                    'requirements': []
                })
            except Exception as e:
                logging.warning(f"MauKerja card error: {e}")
                continue
                
    except Exception as e:
        logging.error(f"MauKerja Error: {e}")
        
    return jobs

# ============================================
# LINKEDIN SCRAPER
# ============================================
def scrape_linkedin(driver, keyword):
    logging.info(f"Scraping LinkedIn for '{keyword}'...")
    jobs = []
    try:
        url = f"https://www.linkedin.com/jobs/search?keywords={keyword}&location=Malaysia&position=1&pageNum=0"
        driver.get(url)
        random_sleep(3, 7)
        
        soup = BeautifulSoup(driver.page_source, 'html.parser')
        job_cards = soup.find_all('li')
        
        logging.info(f"LinkedIn: Found {len(job_cards)} potential items.")
        
        for card in job_cards:
            if len(jobs) >= 20: break
            try:
                title = card.find(class_='base-search-card__title')
                company = card.find(class_='base-search-card__subtitle')
                link = card.find('a', class_='base-card__full-link')
                location = card.find(class_='job-search-card__location')
                
                salary_tag = card.find(class_='job-search-card__salary-info')
                salary = salary_tag.get_text(strip=True) if salary_tag else 'Not specified'
                
                if title and link:
                    jobs.append({
                        'title': title.text.strip(),
                        'company': company.text.strip() if company else 'LinkedIn',
                        'location': location.text.strip() if location else 'Malaysia',
                        'source': 'linkedin',
                        'external_url': link['href'].split('?')[0],
                        'description': 'Apply on LinkedIn',
                        'salary': salary,
                        'requirements': []
                    })
            except:
                continue
    except Exception as e:
        logging.error(f"LinkedIn Error: {e}")
        
    return jobs

# ============================================
# MAIN ENTRY POINT
# ============================================
def main():
    parser = argparse.ArgumentParser(description='Scrape jobs with a keyword.')
    parser.add_argument('--keyword', type=str, default='Software Engineer', help='Job keyword to search for')
    args = parser.parse_args()
    
    keyword = args.keyword
    
    driver = None
    all_jobs = []
    try:
        driver = get_driver()
        
        # Scrape from all 3 sources: JobStreet, MauKerja, LinkedIn
        all_jobs.extend(scrape_jobstreet(driver, keyword))
        all_jobs.extend(scrape_maukerja(driver, keyword))
        all_jobs.extend(scrape_linkedin(driver, keyword))
        
    except Exception as e:
        logging.critical(f"Global Scraper Error: {str(e)}")
    finally:
        if driver:
            driver.quit()
            
    # Output JSON to file
    output_path = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), 'storage', 'app', 'scraped_jobs.json')
    
    try:
        os.makedirs(os.path.dirname(output_path), exist_ok=True)
        
        with open(output_path, 'w', encoding='utf-8') as f:
            json.dump(all_jobs, f, ensure_ascii=False, indent=2)
            
        logging.info(f"Saved {len(all_jobs)} jobs to {output_path}")
        print(json.dumps(all_jobs)) 
        
    except Exception as e:
        logging.error(f"Failed to save output: {e}")

if __name__ == "__main__":
    main()
