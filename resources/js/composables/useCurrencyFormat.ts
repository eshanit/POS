export function useCurrencyFormat() {
  function formatCurrency(amount: number, currency: string): string {
    if (currency === 'USD') {
      return `USD ${amount.toFixed(2)}`
    }
    return `ZIG ${amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
  }
  return { formatCurrency }
}
